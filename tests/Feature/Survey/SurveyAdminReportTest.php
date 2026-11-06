<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Services\SurveyReportService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Services\SurveyPublicService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Phản hồi, thống kê, xuất Excel và phân quyền route quản trị. Chạy trên SQLite :memory: (xem SurveySqliteTestCase).
 */
final class SurveyAdminReportTest extends SurveySqliteTestCase
{
    private KhaoSat $survey;

    protected function setUp(): void
    {
        parent::setUp();
        $svc = app(SurveyService::class);
        $this->survey = $svc->create(['ks_ten' => 'Báo cáo', 'ks_ma' => 'bao-cao', 'ks_so_lan_gui' => 0, 'ks_yeu_cau_thong_tin' => true, 'ks_cho_phep_an_danh' => true], 1);
        $svc->saveStructure($this->survey, [[
            'name' => 'Phần A',
            'questions' => [
                ['type' => 'single', 'text' => 'Màu?', 'options' => [['text' => 'Đỏ'], ['text' => 'Xanh']]],
                ['type' => 'multiple', 'text' => 'Món?', 'options' => [['text' => 'Cơm'], ['text' => 'Phở']]],
                ['type' => 'scale', 'text' => 'Điểm?', 'config' => ['min' => 1, 'max' => 5]],
                ['type' => 'yesno', 'text' => 'Quay lại?'],
                ['type' => 'text', 'text' => 'Ý kiến?'],
            ],
        ]], 1);
        $this->survey = $svc->transition($this->survey->fresh(), SurveyStatus::Open, 1);
    }

    private function opt(string $q, string $label): string
    {
        $id = DB::table('ks_cau_hoi')->where('ch_noidung', $q)->value('ch_id');

        return (string) DB::table('ks_tra_loi')->where('ch_id', $id)->where('trl_noidung', $label)->value('trl_ma');
    }

    private function key(string $q): string
    {
        return (string) DB::table('ks_cau_hoi')->where('ch_noidung', $q)->value('ch_ma');
    }

    /** Một lượt qua đúng đường công khai (service), không chèn thẳng DB. */
    private function participate(string $token, array $answers, array $identity = ['an_danh' => '1'], ?string $day = null, bool $complete = true): void
    {
        $public = app(SurveyPublicService::class);
        $ctx = ['token' => $token, 'fingerprint' => hash('sha256', $token), 'ip_hash' => hash('sha256', 'ip'.$token), 'user_agent' => 'Mozilla/5.0 Test'];
        $p = $public->start($this->survey->fresh(), $identity, $ctx);
        if ($day) {
            DB::table('ks_khao_sat_users')->where('ksu_id', $p->ksu_id)->update(['ksu_created_at' => $day.' 09:00:00']);
        }
        if ($complete) {
            $public->submit($this->survey->fresh(), $p->ksu_ma, $answers, $ctx);
            DB::table('ks_khao_sat_users')->where('ksu_id', $p->ksu_id)->update(['ksu_thoi_luong' => 60 * ($p->ksu_id + 1)]);
        }
    }

    private function seedResponses(): void
    {
        $this->participate('t1', [$this->key('Màu?') => $this->opt('Màu?', 'Đỏ'), $this->key('Món?') => [$this->opt('Món?', 'Cơm'), $this->opt('Món?', 'Phở')], $this->key('Điểm?') => '5', $this->key('Quay lại?') => 'yes', $this->key('Ý kiến?') => '=HYPERLINK("http://evil")'], ['ho_ten' => 'Nguyễn Văn A', 'email' => 'a@example.com', 'dong_y' => '1'], '2026-10-01');
        $this->participate('t2', [$this->key('Màu?') => $this->opt('Màu?', 'Xanh'), $this->key('Điểm?') => '3', $this->key('Quay lại?') => 'no'], ['an_danh' => '1'], '2026-10-01');
        $this->participate('t3', [$this->key('Màu?') => $this->opt('Màu?', 'Đỏ'), $this->key('Món?') => [$this->opt('Món?', 'Cơm')], $this->key('Điểm?') => '4'], ['an_danh' => '1'], '2026-10-02');
        $this->participate('t4', [], ['an_danh' => '1'], '2026-10-02', complete: false);
    }

    public function test_statistics_numbers(): void
    {
        $this->seedResponses();
        $stats = app(SurveyReportService::class)->statistics($this->survey, app(SurveyReportService::class)->normalizeFilters([]));
        $o = $stats['overview'];
        $this->assertSame(4, $o['started']);
        $this->assertSame(3, $o['completed']);
        $this->assertSame(75.0, $o['rate']);
        $this->assertNotNull($o['avg_seconds']);
        $this->assertSame([['day' => '2026-10-01', 'started' => 2, 'completed' => 2], ['day' => '2026-10-02', 'started' => 2, 'completed' => 1]], array_map(fn ($d) => array_intersect_key($d, array_flip(['day', 'started', 'completed'])), $stats['daily']));

        $q = collect($stats['questions'])->keyBy('text');
        $this->assertSame(3, $q['Màu?']['respondents']);
        $this->assertSame(['Đỏ' => 2, 'Xanh' => 1], collect($q['Màu?']['rows'])->pluck('count', 'label')->all());
        $this->assertSame(66.7, $q['Màu?']['rows'][0]['percent']);
        $this->assertSame(['Cơm' => 2, 'Phở' => 1], collect($q['Món?']['rows'])->pluck('count', 'label')->all());
        $this->assertSame(2, $q['Món?']['respondents']);
        $this->assertSame(4.0, (float) $q['Điểm?']['average']);
        $this->assertSame(['Có' => 1, 'Không' => 1], collect($q['Quay lại?']['rows'])->pluck('count', 'label')->all());
        $this->assertSame(1, $q['Ý kiến?']['text_count']);
    }

    public function test_filters_apply_to_list_and_statistics(): void
    {
        $this->seedResponses();
        $r = app(SurveyReportService::class);
        $this->assertSame(4, $r->list($this->survey, $r->normalizeFilters([]))->total());
        $this->assertSame(3, $r->list($this->survey, $r->normalizeFilters(['status' => 'completed']))->total());
        $this->assertSame(1, $r->list($this->survey, $r->normalizeFilters(['status' => 'in_progress']))->total());
        $this->assertSame(1, $r->list($this->survey, $r->normalizeFilters(['anonymous' => '0']))->total());
        $this->assertSame(1, $r->list($this->survey, $r->normalizeFilters(['q' => 'nguyễn']))->total());
        $this->assertSame(1, $r->list($this->survey, $r->normalizeFilters(['q' => 'A@EXAMPLE']))->total());
        $this->assertSame(0, $r->list($this->survey, $r->normalizeFilters(['q' => '%']))->total(), '% là ký tự thường, không phải wildcard');
        $this->assertSame(2, $r->list($this->survey, $r->normalizeFilters(['from' => '2026-10-02', 'to' => '2026-10-02']))->total());
        $f = $r->normalizeFilters(['from' => '2026-10-02', 'to' => '2026-10-01', 'status' => 'hack', 'anonymous' => '9']);
        $this->assertEquals(['from' => '2026-10-01', 'to' => '2026-10-02', 'status' => null, 'anonymous' => null], array_intersect_key($f, array_flip(['from', 'to', 'status', 'anonymous'])));
        $this->assertSame(2, $r->statistics($this->survey, $r->normalizeFilters(['from' => '2026-10-01', 'to' => '2026-10-01']))['overview']['completed']);
    }

    public function test_detail_shows_answers_and_delete_hides_from_stats(): void
    {
        $this->seedResponses();
        $r = app(SurveyReportService::class);
        $first = \Modules\CmsFrontend\Models\KhaoSatUser::query()->orderBy('ksu_id')->first();
        $detail = collect($r->detail($first))->keyBy('question');
        $this->assertSame('Đỏ', $detail['Màu?']['answer']);
        $this->assertSame('Cơm; Phở', $detail['Món?']['answer']);
        $this->assertSame('Có', $detail['Quay lại?']['answer']);
        $this->assertSame('Nguyễn Văn A', SurveyReportService::displayName($first));
        $anon = \Modules\CmsFrontend\Models\KhaoSatUser::query()->where('ksu_an_danh', true)->first();
        $this->assertSame('Ẩn danh', SurveyReportService::displayName($anon));

        $r->deleteParticipation($first);
        $this->assertSame(3, $r->list($this->survey, $r->normalizeFilters([]))->total());
        $this->assertSame(2, $r->statistics($this->survey, $r->normalizeFilters([]))['overview']['completed']);
        $this->assertSame(1, DB::table('ks_khao_sat_users')->whereNotNull('ksu_deleted_at')->count());
        $this->assertGreaterThan(0, DB::table('ks_ket_qua')->where('ksu_id', $first->ksu_id)->count(), 'xóa mềm: dữ liệu câu trả lời vẫn còn');
    }

    private function sheetsOf(object $export): array
    {
        $raw = Excel::raw($export, ExcelWriter::XLSX);
        $tmp = tempnam(sys_get_temp_dir(), 'sv').'.xlsx';
        file_put_contents($tmp, $raw);
        $book = IOFactory::load($tmp);
        @unlink($tmp);
        $out = [];
        foreach ($book->getAllSheets() as $sheet) {
            $out[$sheet->getTitle()] = $sheet->toArray(null, true, false, false);
        }

        return $out;
    }

    public function test_summary_export_has_totals_and_no_personal_data(): void
    {
        $this->seedResponses();
        $r = app(SurveyReportService::class);
        $sheets = $this->sheetsOf($r->summaryExport($this->survey, $r->normalizeFilters([])));
        $this->assertSame(['Tổng quan', 'Theo ngày', 'Theo câu hỏi'], array_keys($sheets));
        $flat = json_encode($sheets, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('75%', $flat);
        $this->assertStringNotContainsString('Nguyễn Văn A', $flat);
        $this->assertStringNotContainsString('a@example.com', $flat);
        $this->assertStringNotContainsString('Mozilla', $flat);
        $questionRows = collect($sheets['Theo câu hỏi'])->filter(fn ($row) => ($row[1] ?? null) === 'Màu?');
        $this->assertCount(2, $questionRows);
    }

    public function test_detail_export_default_excludes_contact_and_technical_fields(): void
    {
        $this->seedResponses();
        $r = app(SurveyReportService::class);
        $sheets = $this->sheetsOf($r->detailExport($this->survey, $r->normalizeFilters([]), false));
        $rows = $sheets['Chi tiết'];
        $this->assertCount(1 + 3, $rows, 'tiêu đề + 3 lượt đã hoàn thành (lượt dở không xuất)');
        $head = $rows[0];
        foreach (['Họ và tên', 'Số điện thoại', 'Email'] as $forbidden) {
            $this->assertNotContains($forbidden, $head);
        }
        $flat = json_encode($rows, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Nguyễn Văn A', $flat);
        $this->assertStringNotContainsString('Mozilla', $flat);
        $this->assertStringNotContainsString(hash('sha256', 'ip'.'t1'), $flat);
        $this->assertStringNotContainsString(hash('sha256', 't1'), $flat);
        // ô người dùng nhập bắt đầu bằng "=" không được thành công thức
        $this->assertStringContainsString("'=HYPERLINK", $flat);
        $this->assertStringNotContainsString('"=HYPERLINK', $flat);
    }

    public function test_detail_export_with_contact_and_filters(): void
    {
        $this->seedResponses();
        $r = app(SurveyReportService::class);
        $rows = $this->sheetsOf($r->detailExport($this->survey, $r->normalizeFilters(['anonymous' => '0']), true))['Chi tiết'];
        $this->assertCount(2, $rows, 'bộ lọc ẩn danh=0 được áp dụng khi xuất');
        $this->assertContains('Họ và tên', $rows[0]);
        $this->assertContains('Nguyễn Văn A', $rows[1]);
        $rows = $this->sheetsOf($r->detailExport($this->survey, $r->normalizeFilters(['from' => '2026-10-02']), false))['Chi tiết'];
        $this->assertCount(1 + 1, $rows, 'chỉ 1 lượt hoàn thành ngày 02/10');
    }

    public function test_formula_sanitizer(): void
    {
        foreach (['=1+1', '+1', '-1', '@x', "\tx"] as $evil) {
            $this->assertSame("'".$evil, SurveyReportService::cell($evil));
        }
        $this->assertSame('bình thường', SurveyReportService::cell('bình thường'));
        $this->assertSame(5, SurveyReportService::cell(5));
    }

    // ------------------------------------------------------------------ route quản trị: cổng chức năng (Feature Registry) + quyền

    public function test_admin_routes_blocked_when_survey_feature_is_off(): void
    {
        app(\Modules\CmsBackend\Features\FeatureRegistry::class)->setEnabled('survey', false);
        $all = array_map(fn ($c) => $c->label(), \Modules\CmsBackend\Enums\PermissionsEnum::cases());
        $this->actingAs($this->user($all));
        $id = $this->survey->ks_id;
        foreach (['/cms/khao-sat', "/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/phan-hoi", "/cms/khao-sat/{$id}/thong-ke", "/cms/khao-sat/{$id}/xuat?type=detail", "/cms/khao-sat/{$id}/xem-truoc", '/cms/khao-sat/luot/1'] as $url) {
            $this->get($url)->assertNotFound();
        }
        foreach (["/cms/khao-sat/{$id}/trang-thai", "/cms/khao-sat/{$id}/xoa", "/cms/khao-sat/{$id}/nhan-ban", "/cms/khao-sat/{$id}/cau-hoi", '/cms/khao-sat'] as $url) {
            $this->post($url)->assertNotFound();
        }
        $this->assertSame('open', DB::table('ks_khao_sat')->value('ks_status'), 'không có thay đổi nào xảy ra');
    }

    public function test_admin_permissions_are_enforced_per_action(): void
    {
        $this->seedResponses();
        $this->withErrorPageSupport();
        $id = $this->survey->ks_id;
        $P = \Modules\CmsBackend\Enums\PermissionsEnum::class;

        // chỉ có khaosat-view (cũ): xem danh sách + phản hồi (tương thích), không xuất/xóa/thống kê/phát hành/tạo
        $this->actingAs($this->user([$P::KHAOSAT_VIEW->label()]));
        $this->get("/cms/khao-sat/{$id}/phan-hoi")->assertOk();
        $this->get('/cms/khao-sat/luot/1')->assertOk();
        $this->get("/cms/khao-sat/{$id}/xuat?type=summary")->assertForbidden();
        $this->get("/cms/khao-sat/{$id}/thong-ke")->assertForbidden();
        $this->post('/cms/khao-sat/luot/1/xoa')->assertForbidden();
        $this->post("/cms/khao-sat/{$id}/trang-thai", ['status' => 'ended'])->assertForbidden();
        $this->post("/cms/khao-sat/{$id}/xoa")->assertForbidden();
        $this->post("/cms/khao-sat/{$id}/cau-hoi", ['structure' => '[]'])->assertForbidden();
        $this->post('/cms/khao-sat', ['ks_ten' => 'x'])->assertForbidden();
        $this->assertSame('open', DB::table('ks_khao_sat')->value('ks_status'));
        $this->assertSame(0, DB::table('ks_khao_sat_users')->whereNotNull('ksu_deleted_at')->count());

        // export riêng
        $this->actingAs($this->user([$P::KHAOSAT_EXPORT->label()]));
        $this->get("/cms/khao-sat/{$id}/xuat?type=summary")->assertOk()->assertHeader('content-disposition');
        $this->get("/cms/khao-sat/{$id}/phan-hoi")->assertForbidden();

        // xóa phản hồi riêng
        $this->actingAs($this->user([$P::KHAOSAT_RESPONSE_DELETE->label()]));
        $this->post('/cms/khao-sat/luot/1/xoa')->assertRedirect();
        $this->assertSame(1, DB::table('ks_khao_sat_users')->whereNotNull('ksu_deleted_at')->count());

        // phát hành riêng: kết thúc khảo sát, sau đó không còn sửa được
        $this->actingAs($this->user([$P::KHAOSAT_PUBLISH->label()]));
        $this->post("/cms/khao-sat/{$id}/trang-thai", ['status' => 'ended'])->assertRedirect();
        $this->assertSame('ended', DB::table('ks_khao_sat')->value('ks_status'));
        $this->post("/cms/khao-sat/{$id}/trang-thai", ['status' => 'open'])->assertRedirect();
        $this->assertSame('ended', DB::table('ks_khao_sat')->value('ks_status'), 'Kết thúc không mở lại được');
        $this->actingAs($this->user([$P::KHAOSAT_UPDATE->label()]));
        $this->post("/cms/khao-sat/{$id}/cau-hoi", ['structure' => '[]'])->assertRedirect();
        $this->assertGreaterThan(0, DB::table('ks_cau_hoi')->whereNull('ch_deleted_at')->count(), 'khảo sát đã kết thúc: cấu trúc không bị đổi');
    }

    public function test_admin_pages_render_with_full_permissions(): void
    {
        $this->seedResponses();
        $all = array_map(fn ($c) => $c->label(), \Modules\CmsBackend\Enums\PermissionsEnum::cases());
        $user = $this->user($all);
        $this->actingAs($user);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $id = $this->survey->ks_id;
        $first = DB::table('ks_khao_sat_users')->orderBy('ksu_id')->value('ksu_id');
        $sections = fn (string $view, array $data) => view($view, $data)->renderSections()['content'];

        $surveys = app(SurveyService::class)->paginate(['q' => '', 'status' => null]);
        $html = $sections('cmsbackend::screens.pages.khao-sat.index', ['surveys' => $surveys, 'filters' => ['q' => '', 'status' => null], 'statuses' => SurveyStatus::cases()]);
        $this->assertStringContainsString('Báo cáo', $html);

        $survey = $this->survey->fresh();
        $html = $sections('cmsbackend::screens.pages.khao-sat.edit', ['survey' => $survey, 'caps' => app(SurveyService::class)->capabilities($survey), 'structure' => app(SurveyService::class)->structureForBuilder($survey)]);
        $this->assertStringContainsString('survey-structure-form', $html);

        $r = app(SurveyReportService::class);
        $filters = $r->normalizeFilters([]);
        $html = $sections('cmsbackend::screens.pages.khao-sat.responses', ['survey' => $survey, 'filters' => $filters, 'participations' => $r->list($survey, $filters)]);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString('Ẩn danh', $html);
        $this->assertStringContainsString('type=detail', $html);

        $participation = \Modules\CmsFrontend\Models\KhaoSatUser::query()->findOrFail($first);
        $html = $sections('cmsbackend::screens.pages.khao-sat.show', ['participation' => $participation, 'survey' => $survey, 'answers' => $r->detail($participation)]);
        $this->assertStringContainsString('Cơm', $html);
        $this->assertStringNotContainsString($participation->ksu_dau_vet, $html);
        $this->assertStringNotContainsString($participation->ksu_ip_hash, $html);

        $html = $sections('cmsbackend::screens.pages.khao-sat.statistics', ['survey' => $survey, 'filters' => $filters, 'stats' => $r->statistics($survey, $filters)]);
        $this->assertStringContainsString('75%', $html);
    }
}
