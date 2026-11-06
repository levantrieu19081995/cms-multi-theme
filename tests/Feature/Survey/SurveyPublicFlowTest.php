<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Luồng công khai làm khảo sát, kiểm tra trên SQLite trong bộ nhớ (KHÔNG chạm DB thật).
 *
 * Chạy và điều kiện: xem SurveySqliteTestCase.
 */
final class SurveyPublicFlowTest extends SurveySqliteTestCase
{
    private const TOKEN_A = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    private const TOKEN_B = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

    /** @return array{survey: KhaoSat, q: array<string, string>} */
    private function openSurvey(array $info = [], bool $open = true): array
    {
        $service = app(SurveyService::class);
        $survey = $service->create($info + ['ks_ten' => 'Khảo sát thử', 'ks_ma' => 'thu', 'ks_so_lan_gui' => 1], 1);
        $service->saveStructure($survey, [[
            'name' => 'Phần 1',
            'questions' => [
                ['type' => 'single', 'text' => 'Chọn một', 'required' => true, 'options' => [['text' => 'A'], ['text' => 'B']]],
                ['type' => 'multiple', 'text' => 'Chọn nhiều', 'options' => [['text' => 'X'], ['text' => 'Y']]],
                ['type' => 'text', 'text' => 'Ý kiến ngắn'],
            ],
        ], ['name' => 'Phần 2', 'questions' => [
            ['type' => 'scale', 'text' => 'Chấm điểm', 'required' => true, 'config' => ['min' => 1, 'max' => 5]],
            ['type' => 'textarea', 'text' => 'Góp ý dài'],
            ['type' => 'yesno', 'text' => 'Đồng ý?'],
        ]]], 1);
        if ($open) {
            $survey = $service->transition($survey->fresh(), \Modules\CmsBackend\Enums\SurveyStatus::Open, 1);
        }
        $keys = DB::table('ks_cau_hoi')->pluck('ch_ma', 'ch_noidung')->all();

        return ['survey' => $survey, 'q' => $keys];
    }

    private function optionKey(string $questionText, string $label): string
    {
        $chId = DB::table('ks_cau_hoi')->where('ch_noidung', $questionText)->value('ch_id');

        return (string) DB::table('ks_tra_loi')->where('ch_id', $chId)->where('trl_noidung', $label)->value('trl_ma');
    }

    private function browser(string $token, ?string $participationCode = null, int $surveyId = 1): static
    {
        $this->withCookie('ks_client', $token);
        if ($participationCode !== null) {
            $this->withSession(['survey.active.'.$surveyId => $participationCode]);
        }

        return $this;
    }

    private function validAnswers(array $q): array
    {
        return [
            $q['Chọn một'] => $this->optionKey('Chọn một', 'A'),
            $q['Chọn nhiều'] => [$this->optionKey('Chọn nhiều', 'X'), $this->optionKey('Chọn nhiều', 'Y')],
            $q['Ý kiến ngắn'] => 'ổn',
            $q['Chấm điểm'] => '4',
            $q['Góp ý dài'] => str_repeat('a', 300),
            $q['Đồng ý?'] => 'yes',
        ];
    }

    public function test_feature_off_returns_404_and_theme_does_not_gate(): void
    {
        $this->openSurvey();
        $registry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);
        $registry->setEnabled('survey', false);
        foreach (['default', 'nam-binh-duong', 'ban-de-an'] as $theme) {
            $this->theme($theme);
            $this->get('/khao-sat/thu')->assertNotFound();
            $this->get('/khao-sat')->assertNotFound();
            $this->post('/khao-sat/thu/bat-dau')->assertNotFound();
        }
        $registry->setEnabled('survey', true);
        // chức năng bật thì theme nào cũng dùng được: theme chỉ đổi giao diện
        foreach (['default', 'ban-de-an'] as $theme) {
            $this->theme($theme);
            $this->get('/khao-sat/thu')->assertOk()->assertSee('Khảo sát thử');
        }
        $this->assertSame(0, DB::table('ks_khao_sat_users')->count(), 'xem trang không tạo dữ liệu');
    }

    public function test_status_and_time_gate(): void
    {
        ['survey' => $survey] = $this->openSurvey();
        $svc = app(SurveyService::class);
        $this->get('/khao-sat/khong-co')->assertNotFound()->assertSee('Không tìm thấy khảo sát');

        $svc->transition($survey->fresh(), \Modules\CmsBackend\Enums\SurveyStatus::Paused, 1);
        $this->get('/khao-sat/thu')->assertOk()->assertSee('tạm dừng')->assertDontSee('Bắt đầu làm khảo sát');

        $svc->transition($survey->fresh(), \Modules\CmsBackend\Enums\SurveyStatus::Open, 1);
        DB::table('ks_khao_sat')->update(['ks_bat_dau' => now()->addDay()]);
        $this->get('/khao-sat/thu')->assertOk()->assertSee('chưa mở');

        DB::table('ks_khao_sat')->update(['ks_bat_dau' => null, 'ks_ket_thuc' => now()->subMinute()]);
        $this->get('/khao-sat/thu')->assertOk()->assertSee('đã kết thúc');
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau')->assertRedirect(route('cms.frontend.khaosat.show', 'thu'));
        $this->assertSame(0, DB::table('ks_khao_sat_users')->count());
    }

    public function test_draft_is_invisible(): void
    {
        $this->openSurvey(open: false);
        $this->get('/khao-sat/thu')->assertNotFound();
        $this->get('/khao-sat/thu/lam-bai')->assertNotFound();
    }

    public function test_full_flow_saves_answers_once(): void
    {
        ['q' => $q] = $this->openSurvey();

        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau')->assertRedirect(route('cms.frontend.khaosat.form', 'thu'));
        $this->assertSame(1, DB::table('ks_khao_sat_users')->where('ksu_trang_thai', 'in_progress')->count());
        $code = (string) DB::table('ks_khao_sat_users')->value('ksu_ma');

        $page = $this->browser(self::TOKEN_A, $code)->get('/khao-sat/thu/lam-bai')->assertOk();
        $page->assertSee('Chọn một')->assertSee($q['Chọn một'])->assertDontSee('ksu_id')->assertDontSee('ch_id');

        // thiếu câu bắt buộc → không lưu
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => [$q['Ý kiến ngắn'] => 'x']])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('ks_ket_qua')->count());
        // đáp án không thuộc câu hỏi → không lưu
        $bad = $this->validAnswers($q);
        $bad[$q['Chọn một']] = $this->optionKey('Chọn nhiều', 'X');
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $bad])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('ks_ket_qua')->count());
        // điểm ngoài thang
        $bad = $this->validAnswers($q);
        $bad[$q['Chấm điểm']] = '9';
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $bad])->assertSessionHasErrors();

        // hợp lệ
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)])->assertRedirect(route('cms.frontend.khaosat.thanks', 'thu'));
        $this->assertSame(7, DB::table('ks_ket_qua')->count(), '1 + 2 (nhiều) + text + scale + textarea + yesno');
        $row = DB::table('ks_khao_sat_users')->first();
        $this->assertSame('completed', $row->ksu_trang_thai);
        $this->assertNotNull($row->ksu_hoan_thanh_at);
        $this->assertNotNull($row->ksu_thoi_luong);
        $this->assertSame(1, (int) $row->ksu_an_danh);
        $long = DB::table('ks_ket_qua')->whereNotNull('kskq_van_ban')->where('kskq_van_ban', 'like', 'aaaa%')->first();
        $this->assertSame(300, mb_strlen($long->kskq_van_ban));
        $this->assertLessThanOrEqual(255, mb_strlen($long->kskq_trl_value));

        // gửi lại (F5 / nhấp đúp): không thêm dữ liệu
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)])->assertRedirect(route('cms.frontend.khaosat.thanks', 'thu'));
        $this->assertSame(7, DB::table('ks_ket_qua')->count());
        $this->assertSame(1, DB::table('ks_khao_sat_users')->count());
    }

    public function test_per_browser_limit_and_other_browser_allowed(): void
    {
        ['q' => $q] = $this->openSurvey();
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $code = (string) DB::table('ks_khao_sat_users')->value('ksu_ma');
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)]);

        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau')->assertSessionHasErrors('survey');
        $this->assertSame(1, DB::table('ks_khao_sat_users')->count(), 'trình duyệt đã gửi đủ lần: không tạo lượt mới');

        $this->browser(self::TOKEN_B)->post('/khao-sat/thu/bat-dau')->assertRedirect(route('cms.frontend.khaosat.form', 'thu'));
        $this->assertSame(2, DB::table('ks_khao_sat_users')->count());
    }

    public function test_unlimited_when_limit_is_zero_and_start_is_resumed_not_duplicated(): void
    {
        $this->openSurvey(['ks_so_lan_gui' => 0]);
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $this->assertSame(1, DB::table('ks_khao_sat_users')->count(), 'bắt đầu lặp lại dùng lại lượt đang làm dở');
    }

    public function test_submit_without_start_or_with_foreign_session_saves_nothing(): void
    {
        ['q' => $q] = $this->openSurvey();
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)])->assertSessionHasErrors('survey');
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $code = (string) DB::table('ks_khao_sat_users')->value('ksu_ma');
        // mã lượt của trình duyệt A bị dùng từ trình duyệt B
        $this->browser(self::TOKEN_B, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)])->assertSessionHasErrors('survey');
        $this->assertSame(0, DB::table('ks_ket_qua')->count());
        $this->browser(self::TOKEN_B, $code)->get('/khao-sat/thu/lam-bai')->assertRedirect(route('cms.frontend.khaosat.show', 'thu'));
    }

    public function test_closing_between_start_and_submit_blocks_the_submit(): void
    {
        ['q' => $q] = $this->openSurvey();
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $code = (string) DB::table('ks_khao_sat_users')->value('ksu_ma');
        DB::table('ks_khao_sat')->update(['ks_status' => 'ended']);
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q)])->assertRedirect();
        $this->assertSame(0, DB::table('ks_ket_qua')->count());
    }

    public function test_honeypot_discards_bot_submission(): void
    {
        ['q' => $q] = $this->openSurvey();
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $code = (string) DB::table('ks_khao_sat_users')->value('ksu_ma');
        $this->browser(self::TOKEN_A, $code)->post('/khao-sat/thu/gui', ['answers' => $this->validAnswers($q), 'sv_website' => 'http://spam'])->assertRedirect(route('cms.frontend.khaosat.show', 'thu'));
        $this->assertSame(0, DB::table('ks_ket_qua')->count());
    }

    public function test_identity_modes(): void
    {
        $this->openSurvey(['ks_yeu_cau_thong_tin' => true, 'ks_cho_phep_an_danh' => false]);
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', [])->assertSessionHasErrors(['dong_y', 'ho_ten']);
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', ['ho_ten' => 'Nguyễn A', 'dong_y' => '1'])->assertSessionHasErrors(['so_dien_thoai']);
        // ẩn danh bị cấm: tham số `an_danh` bị bỏ qua, vẫn phải khai báo thông tin đầy đủ
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', ['an_danh' => '1'])->assertSessionHasErrors(['dong_y', 'ho_ten']);
        $this->assertSame(0, DB::table('ks_khao_sat_users')->count());
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', ['ho_ten' => 'Nguyễn A', 'email' => 'a@example.com', 'dong_y' => '1', 'an_danh' => '1'])->assertRedirect();
        $this->assertSame(1, DB::table('ks_khao_sat_users')->count());
        $row = DB::table('ks_khao_sat_users')->first();
        $this->assertSame('Nguyễn A', $row->ksu_ho_ten);
        $this->assertSame(0, (int) $row->ksu_an_danh);
        $this->assertNull($row->ksu_ket_qua);
    }

    public function test_anonymous_allowed_skips_identity(): void
    {
        $this->openSurvey(['ks_yeu_cau_thong_tin' => true, 'ks_cho_phep_an_danh' => true]);
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', ['an_danh' => '1'])->assertRedirect(route('cms.frontend.khaosat.form', 'thu'));
        $row = DB::table('ks_khao_sat_users')->first();
        $this->assertSame('', $row->ksu_ho_ten);
        $this->assertSame(1, (int) $row->ksu_an_danh);
    }

    public function test_no_identity_requested_collects_nothing(): void
    {
        $this->openSurvey(['ks_yeu_cau_thong_tin' => false]);
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau', ['ho_ten' => 'Không nên lưu', 'email' => 'x@example.com'])->assertRedirect();
        $row = DB::table('ks_khao_sat_users')->first();
        $this->assertSame('', $row->ksu_ho_ten);
        $this->assertNull($row->ksu_email);
    }

    public function test_no_ip_or_raw_cookie_is_stored(): void
    {
        $this->openSurvey();
        $this->browser(self::TOKEN_A)->post('/khao-sat/thu/bat-dau');
        $row = (array) DB::table('ks_khao_sat_users')->first();
        $this->assertStringNotContainsString(self::TOKEN_A, json_encode($row));
        $this->assertStringNotContainsString('127.0.0.1', json_encode($row));
        $this->assertSame(64, strlen($row['ksu_dau_vet']));
    }

    public function test_theme_views_override_and_fall_back_to_default(): void
    {
        $this->openSurvey();
        $this->theme('nam-binh-duong');
        $this->get('/khao-sat/thu')->assertOk()->assertSee('#102A43', false)->assertSee('content="steps"', false);
        $this->theme('ban-de-an');
        $this->get('/khao-sat/thu')->assertOk()->assertSee('#1F4E6D', false)->assertSee('content="bar"', false);
        // theme không có view riêng → giao diện mặc định, không lỗi
        $this->theme('default');
        $this->get('/khao-sat/thu')->assertOk()->assertSee('#2e7d32', false)->assertSee('Khảo sát thử');
    }

    public function test_intro_html_is_rendered_sanitized_and_names_are_escaped(): void
    {
        $this->openSurvey(['ks_ten' => '<b>Tên</b>', 'ks_gioi_thieu' => '<p>Chào</p><script>alert(1)</script>']);
        $this->get('/khao-sat/thu')->assertOk()->assertSee('&lt;b&gt;Tên&lt;/b&gt;', false)->assertSee('<p>Chào</p>', false)->assertDontSee('<script>alert', false);
    }

    public function test_preview_view_renders_without_submit_and_with_real_keys(): void
    {
        ['survey' => $survey, 'q' => $q] = $this->openSurvey(open: false);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag); // do ShareErrorsFromSession làm ở request thật
        $html = view('cmsfrontend::screens.pages.khao-sat.lam-bai', [
            'survey' => $survey,
            'form' => app(\Modules\CmsBackend\Services\SurveyFormService::class)->schema($survey),
            'preview' => true,
            'participation' => null,
            'identity' => ['collect' => false, 'anonymous_allowed' => true],
        ])->render();
        $this->assertStringContainsString('Chế độ xem trước', $html);
        $this->assertStringContainsString('data-submit disabled', preg_replace('/\s+/', ' ', $html));
        $this->assertStringContainsString('answers['.$q['Chọn một'].']', $html);
        $this->assertSame(2, substr_count($html, 'data-step="'), 'hai phần = hai bước (không dùng thứ tự 1..N làm khóa câu hỏi)');
    }
}
