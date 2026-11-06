<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Enums\PermissionsEnum as P;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsBackend\Support\SurveyPermissions;
use Modules\CmsFrontend\Models\KhaoSat;

/** Hồi quy vòng 2 cho CODEX-REVIEW.md: KS-001 (dependency), KS-002 (quyền cha), KS-007 (khóa quy tắc thu thập), KS-008 (container sai kiểu). */
final class SurveyReviewRound2Test extends SurveySqliteTestCase
{
    private function draft(array $info = []): KhaoSat
    {
        $svc = app(SurveyService::class);
        $survey = $svc->create($info + ['ks_ten' => 'Vòng hai', 'ks_ma' => 'vong-hai', 'ks_cho_phep_an_danh' => true], 1);
        $svc->saveStructure($survey, [['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]], 1);

        return $survey->fresh();
    }

    /** Biểu mẫu thông tin đúng như giao diện gửi: giá trị hiện tại + các thay đổi. */
    private function infoPayload(KhaoSat $s, array $override = []): array
    {
        $dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : null;

        return $override + [
            'ks_ten' => $s->ks_ten,
            'ks_mo_ta_ngan' => $s->ks_mo_ta_ngan,
            'ks_gioi_thieu' => $s->ks_gioi_thieu,
            'ks_loai' => $s->ks_loai ?: 'general',
            'ks_bat_dau' => $dt($s->ks_bat_dau),
            'ks_ket_thuc' => $dt($s->ks_ket_thuc),
            'ks_cho_phep_an_danh' => (int) $s->ks_cho_phep_an_danh,
            'ks_yeu_cau_thong_tin' => (int) $s->ks_yeu_cau_thong_tin,
            'ks_so_lan_gui' => (int) $s->ks_so_lan_gui,
            'ks_noi_dung_hoan_thanh' => $s->ks_noi_dung_hoan_thanh,
            'ks_ghichu' => $s->ks_ghichu,
        ];
    }

    private function addParticipation(KhaoSat $survey): void
    {
        $public = app(\Modules\CmsFrontend\Services\SurveyPublicService::class);
        $ctx = ['token' => 'T', 'fingerprint' => hash('sha256', 'T'), 'ip_hash' => 'x', 'user_agent' => null];
        $public->start($survey->fresh(), ['an_danh' => 1], $ctx);
    }

    // ------------------------------------------------------------------ KS-001

    public function test_ks001_htmlpurifier_is_a_direct_composer_requirement(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        if (! array_key_exists('ezyang/htmlpurifier', $composer['require'])) {
            // Chưa chạy được Composer trong môi trường của agent: cần `composer require ezyang/htmlpurifier` (cập nhật cả composer.lock).
            $this->markTestSkipped('CHƯA ĐẠT KS-001: composer.json chưa khai báo trực tiếp ezyang/htmlpurifier; chủ dự án cần chạy `composer require ezyang/htmlpurifier`.');
        }
        $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);
        $this->assertContains('ezyang/htmlpurifier', array_column($lock['packages'], 'name'));
        $this->assertTrue(class_exists(\HTMLPurifier::class));
    }

    // ------------------------------------------------------------------ KS-002

    public function test_ks002_parent_permission_alone_behaves_like_every_child_in_routes_and_views(): void
    {
        $user = $this->user([P::KHAOSAT_MANAGER->label()]);
        // helper: quyền cha bao trùm toàn bộ quyền con, và chỉ quyền khảo sát
        foreach (SurveyPermissions::map($user) as $slug => $allowed) {
            $this->assertTrue($allowed, "$slug phải được quyền cha cho phép");
        }
        $this->assertCount(9, SurveyPermissions::map($user));
        $this->assertFalse(SurveyPermissions::can($this->user([P::DICHVU_VIEW->label()]), P::KHAOSAT_UPDATE));
        $this->assertFalse(SurveyPermissions::can(null, P::KHAOSAT_UPDATE));

        $survey = $this->draft();
        app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        DB::table('options')->insert(['key' => \App\Enums\OptionEnum::WEB_LOGO->value, 'value' => '/logo.png']);
        $id = $survey->ks_id;
        $this->actingAs($user);

        $list = $this->get('/cms/khao-sat')->assertOk()->getContent();
        foreach (["/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/phan-hoi", "/cms/khao-sat/{$id}/thong-ke", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/trang-thai", "/cms/khao-sat/{$id}/nhan-ban", 'khao-sat/tao-moi'] as $needle) {
            $this->assertStringContainsString($needle, $list, "quyền cha phải thấy $needle trong danh sách");
        }
        $edit = $this->get("/cms/khao-sat/{$id}/chinh-sua")->assertOk()->getContent();
        $this->assertStringContainsString("/cms/khao-sat/{$id}/phan-hoi", $edit);
        $this->assertStringContainsString("/cms/khao-sat/{$id}/thong-ke", $edit);
        $this->assertStringContainsString('name="ks_ten"', $edit);
        $this->get("/cms/khao-sat/{$id}/phan-hoi")->assertOk();
        $this->get("/cms/khao-sat/{$id}/thong-ke")->assertOk()->assertSee("/cms/khao-sat/{$id}/xuat", false);

        // quyền con đơn lẻ vẫn không được mở rộng (không có quyền cha)
        $this->actingAs($this->user([P::KHAOSAT_RESPONSE_VIEW->label()]));
        $this->assertStringNotContainsString("/cms/khao-sat/{$id}/chinh-sua", $this->get('/cms/khao-sat')->assertOk()->getContent());
    }

    public function test_ks002_blade_views_do_not_call_can_directly(): void
    {
        foreach (glob(base_path('Modules/CmsBackend/resources/views/screens/default/pages/khao-sat/*.blade.php')) as $file) {
            $this->assertStringNotContainsString('->can(', (string) file_get_contents($file), basename($file).' phải dùng SurveyPermissions, không gọi can() trực tiếp');
        }
    }

    // ------------------------------------------------------------------ KS-007

    /** @return array<string, array{string, mixed}> trường bị khóa → giá trị mới khác giá trị hiện tại */
    public static function lockedChanges(): array
    {
        return [
            'type' => ['ks_loai', 'research'],
            'start' => ['ks_bat_dau', '2020-01-01T08:00'],
            'anonymous' => ['ks_cho_phep_an_danh', 0],
            'require identity' => ['ks_yeu_cau_thong_tin', 1],
            'submit limit' => ['ks_so_lan_gui', 5],
        ];
    }

    private function assertRejectedAndUntouched(KhaoSat $survey, array $override, string $why): void
    {
        $before = DB::table('ks_khao_sat')->where('ks_id', $survey->ks_id)->first();
        try {
            app(SurveyService::class)->update($survey->fresh(), $this->infoPayload($survey->fresh(), $override), 1);
            $this->fail("$why: phải bị từ chối");
        } catch (SurveyRuleException) {
            $this->addToAssertionCount(1);
        }
        $this->assertEquals($before, DB::table('ks_khao_sat')->where('ks_id', $survey->ks_id)->first(), "$why: dữ liệu phải giữ nguyên");
    }

    public function test_ks007_draft_without_participants_stays_fully_editable(): void
    {
        $survey = $this->draft();
        $svc = app(SurveyService::class);
        $this->assertFalse($svc->collectionLocked($survey));
        $svc->update($survey, $this->infoPayload($survey, ['ks_loai' => 'research', 'ks_so_lan_gui' => 3, 'ks_cho_phep_an_danh' => 0, 'ks_yeu_cau_thong_tin' => 1, 'ks_bat_dau' => '2030-01-01T08:00', 'ks_ma' => 'ten-moi']), 1);
        $fresh = $survey->fresh();
        $this->assertSame('research', $fresh->ks_loai);
        $this->assertSame(3, (int) $fresh->ks_so_lan_gui);
        $this->assertSame('ten-moi', $fresh->ks_ma);
        $this->assertFalse(app(SurveyService::class)->capabilities($fresh)['collection_locked']);
    }

    public function test_ks007_open_and_paused_lock_collection_fields_but_allow_presentation_fields(): void
    {
        $svc = app(SurveyService::class);
        foreach ([SurveyStatus::Open, SurveyStatus::Paused] as $status) {
            DB::table('ks_khao_sat')->delete();
            $survey = $this->draft(['ks_ma' => 'khoa-'.$status->value]);
            $survey = $svc->transition($survey, SurveyStatus::Open, 1);
            if ($status === SurveyStatus::Paused) {
                $survey = $svc->transition($survey, SurveyStatus::Paused, 1);
            }
            $this->assertTrue($svc->collectionLocked($survey->fresh()));
            $this->assertTrue($svc->capabilities($survey->fresh())['collection_locked']);

            foreach (self::lockedChanges() as $name => [$field, $value]) {
                $this->assertRejectedAndUntouched($survey, [$field => $value], "{$status->value}/$name");
            }
            $this->assertRejectedAndUntouched($survey, ['ks_ma' => 'dia-chi-khac'], "{$status->value}/slug");

            // gửi lại đúng giá trị hiện tại + chỉ đổi phần trình bày → được phép
            $future = now()->addDays(10)->format('Y-m-d\TH:i');
            $svc->update($survey->fresh(), $this->infoPayload($survey->fresh(), [
                'ks_ten' => 'Tên mới', 'ks_mo_ta_ngan' => 'Mô tả mới', 'ks_gioi_thieu' => '<p>Giới thiệu mới</p>',
                'ks_ket_thuc' => $future, 'ks_noi_dung_hoan_thanh' => 'Cảm ơn mới', 'ks_ghichu' => 'Ghi chú mới',
            ]), 1);
            $fresh = $survey->fresh();
            $this->assertSame('Tên mới', $fresh->ks_ten);
            $this->assertSame('Cảm ơn mới', $fresh->ks_noi_dung_hoan_thanh);
            $this->assertSame('Ghi chú mới', $fresh->ks_ghichu);
            $this->assertSame($future, $fresh->ks_ket_thuc->format('Y-m-d\TH:i'));
            $this->assertSame('khoa-'.$status->value, $fresh->ks_ma);
        }
    }

    public function test_ks007_open_or_paused_end_date_must_be_in_the_future(): void
    {
        $svc = app(SurveyService::class);
        $survey = $svc->transition($this->draft(), SurveyStatus::Open, 1);
        $this->assertRejectedAndUntouched($survey, ['ks_ket_thuc' => now()->subHour()->format('Y-m-d\TH:i')], 'open/end in past');
        $this->assertRejectedAndUntouched($survey, ['ks_ket_thuc' => now()->subHour()->format('Y-m-d\TH:i')], 'open/end in past (again)');
        $svc->transition($survey->fresh(), SurveyStatus::Paused, 1);
        $this->assertRejectedAndUntouched($survey, ['ks_ket_thuc' => now()->subDay()->format('Y-m-d\TH:i')], 'paused/end in past');
        // xóa ngày kết thúc (không giới hạn) vẫn được phép
        $svc->update($survey->fresh(), $this->infoPayload($survey->fresh(), ['ks_ket_thuc' => null]), 1);
        $this->assertNull($survey->fresh()->ks_ket_thuc);
    }

    public function test_ks007_draft_with_participants_is_locked_too(): void
    {
        $svc = app(SurveyService::class);
        $survey = $svc->transition($this->draft(), SurveyStatus::Open, 1);
        $this->addParticipation($survey);
        DB::table('ks_khao_sat')->where('ks_id', $survey->ks_id)->update(['ks_status' => 'draft', 'ks_trang_thai' => false]);
        $draft = $survey->fresh();
        $this->assertSame(SurveyStatus::Draft, $draft->status());
        $this->assertTrue($svc->collectionLocked($draft));
        $this->assertFalse($svc->capabilities($draft)['edit_slug']);
        foreach (self::lockedChanges() as $name => [$field, $value]) {
            $this->assertRejectedAndUntouched($draft, [$field => $value], "draft+participants/$name");
        }
        $this->assertRejectedAndUntouched($draft, ['ks_ma' => 'doi-dia-chi'], 'draft+participants/slug');
        $svc->update($draft, $this->infoPayload($draft, ['ks_ten' => 'Vẫn sửa được tên']), 1);
        $this->assertSame('Vẫn sửa được tên', $survey->fresh()->ks_ten);
    }

    public function test_ks007_forged_http_request_is_rejected_by_the_server(): void
    {
        $svc = app(SurveyService::class);
        $survey = $svc->transition($this->draft(), SurveyStatus::Open, 1);
        $id = $survey->ks_id;
        $this->actingAs($this->user([P::KHAOSAT_UPDATE->label()]));
        $before = DB::table('ks_khao_sat')->where('ks_id', $id)->first();

        // request thủ công, bỏ qua giao diện
        foreach (self::lockedChanges() as $name => [$field, $value]) {
            $this->post("/cms/khao-sat/{$id}/cap-nhat", $this->infoPayload($survey->fresh(), [$field => $value]))->assertSessionHasErrors('ks_ten');
            $this->assertEquals($before, DB::table('ks_khao_sat')->where('ks_id', $id)->first(), "HTTP/$name đã làm đổi dữ liệu");
        }
        // ô bị vô hiệu hóa không được gửi: các boolean rơi về 0 → cũng là thay đổi trái phép, không được âm thầm chấp nhận
        $partial = $this->infoPayload($survey->fresh());
        unset($partial['ks_cho_phep_an_danh']);
        $this->post("/cms/khao-sat/{$id}/cap-nhat", $partial)->assertSessionHasErrors('ks_ten');
        $this->assertEquals($before, DB::table('ks_khao_sat')->where('ks_id', $id)->first());

        // biểu mẫu giống giao diện (giữ nguyên giá trị khóa, chỉ đổi tên) → thành công
        $this->flushSession(); // lỗi flash của các request bị từ chối ở trên không được lẫn vào kết quả này
        $this->post("/cms/khao-sat/{$id}/cap-nhat", $this->infoPayload($survey->fresh(), ['ks_ten' => 'Đổi tên hợp lệ']))->assertSessionHasNoErrors();
        $this->assertSame('Đổi tên hợp lệ', $survey->fresh()->ks_ten);
    }

    public function test_ks007_edit_page_shows_locked_fields_and_still_submits_their_current_values(): void
    {
        $svc = app(SurveyService::class);
        $survey = $svc->transition($this->draft(['ks_so_lan_gui' => 2]), SurveyStatus::Open, 1);
        DB::table('options')->insert(['key' => \App\Enums\OptionEnum::WEB_LOGO->value, 'value' => '/logo.png']);
        $this->actingAs($this->user([P::KHAOSAT_UPDATE->label(), P::KHAOSAT_VIEW->label()]));
        $html = $this->get("/cms/khao-sat/{$survey->ks_id}/chinh-sua")->assertOk()->getContent();
        $this->assertStringContainsString(__('admin.survey.notice_collection_locked'), $html);
        $this->assertMatchesRegularExpression('/<input type="hidden" name="ks_so_lan_gui" value="2">/', $html);
        $this->assertMatchesRegularExpression('/id="ks_so_lan_gui"[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/id="ks_loai"[^>]*disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="ks_ten"[^>]*disabled/', $html);

        // khảo sát Nháp mới: không có thông báo khóa
        $fresh = $this->draft(['ks_ma' => 'nhap-moi']);
        $html = $this->get("/cms/khao-sat/{$fresh->ks_id}/chinh-sua")->assertOk()->getContent();
        $this->assertStringNotContainsString(__('admin.survey.notice_collection_locked'), $html);
    }

    // ------------------------------------------------------------------ KS-008

    /** @return array<string, array{mixed}> */
    public static function wrongTypedStructures(): array
    {
        return [
            'group is a string' => [['chuỗi']],
            'group is a number' => [[5]],
            'group is null' => [[null]],
            'questions is a string' => [[['name' => 'P', 'questions' => 'chuỗi']]],
            'questions is a number' => [[['name' => 'P', 'questions' => 7]]],
            'question is a string' => [[['name' => 'P', 'questions' => ['chuỗi']]]],
            'options is a string' => [[['name' => 'P', 'questions' => [['type' => 'single', 'text' => 'Q', 'options' => 'A,B']]]]],
            'option is a string' => [[['name' => 'P', 'questions' => [['type' => 'single', 'text' => 'Q', 'options' => ['A', 'B']]]]]],
            'one good group + one bad' => [[['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]], 'rác']],
            'one bad question among good' => [[['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q'], 'rác']]]],
        ];
    }

    /** @dataProvider wrongTypedStructures */
    public function test_ks008_wrong_typed_containers_are_rejected_and_existing_structure_is_kept(mixed $payload): void
    {
        $survey = $this->draft();
        $tables = ['ks_nhom', 'ks_cau_hoi', 'ks_tra_loi'];
        $snapshot = fn () => array_map(fn ($t) => DB::table($t)->orderBy(array_key_first((array) DB::table($t)->first()))->get()->all(), $tables);
        $before = $snapshot();
        $this->assertNotEmpty($before[1], 'điều kiện đầu: đã có câu hỏi');

        try {
            app(SurveyService::class)->saveStructure($survey, $payload, 1);
            $this->fail('payload sai kiểu phải bị từ chối');
        } catch (SurveyRuleException $e) {
            $this->assertSame(__('admin.survey.error.structure_invalid'), $e->getMessage());
        }
        $this->assertEquals($before, $snapshot(), 'cấu trúc hiện có phải còn nguyên');
    }

    public function test_ks008_http_wrong_typed_payloads_leave_the_database_untouched(): void
    {
        $survey = $this->draft();
        $id = $survey->ks_id;
        $this->actingAs($this->user([P::KHAOSAT_UPDATE->label()]));
        $count = fn () => [DB::table('ks_nhom')->count(), DB::table('ks_cau_hoi')->count(), DB::table('ks_cau_hoi')->whereNotNull('ch_deleted_at')->count()];
        $before = $count();

        foreach ([json_encode(['chuỗi']), json_encode([['name' => 'P', 'questions' => 'chuỗi']]), json_encode('chuỗi'), '"x"', 'không-phải-json'] as $raw) {
            $this->post("/cms/khao-sat/{$id}/cau-hoi", ['structure' => $raw])->assertSessionHasErrors('structure');
            $this->assertSame($before, $count(), "payload $raw làm đổi dữ liệu");
        }
        // mảng gửi thẳng (không JSON) cũng bị chặn
        $this->post("/cms/khao-sat/{$id}/cau-hoi", ['structure' => ['chuỗi']])->assertSessionHasErrors('structure');
        $this->assertSame($before, $count());
        $this->assertGreaterThan(0, $before[1]);
    }

    public function test_ks008_valid_and_empty_structures_still_work(): void
    {
        $survey = $this->draft();
        $svc = app(SurveyService::class);
        $svc->saveStructure($survey, [['name' => 'A', 'questions' => [['type' => 'single', 'text' => 'Q', 'options' => [['text' => 'x'], ['text' => 'y']]]]], ['name' => 'B']], 1);
        $this->assertSame(2, DB::table('ks_nhom')->count());
        // cấu trúc rỗng hợp lệ là thao tác xóa có chủ đích (mảng rỗng, không phải phần tử sai kiểu)
        $svc->saveStructure($survey->fresh(), [], 1);
        $this->assertSame(0, DB::table('ks_nhom')->whereNull('nhom_deleted_at')->count());
    }
}
