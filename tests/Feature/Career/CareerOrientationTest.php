<?php

declare(strict_types=1);

namespace Tests\Feature\Career;

use App\Models\Option;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\CareerService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\CareerGroup;
use Modules\CmsFrontend\Models\CareerMajor;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;
use Modules\CmsFrontend\Services\SurveyPublicService;
use Tests\TestCase;

/**
 * Định hướng nghề nghiệp (RIASEC) trên nền Khảo sát. Chạy trên database dev nhưng mọi thay đổi nằm trong transaction và được rollback
 * (xem ALLOW_DEV_DB_TRANSACTIONAL_TESTS trong tests/TestCase.php).
 */
final class CareerOrientationTest extends TestCase
{
    private SurveyService $surveys;

    private CareerService $career;

    private SurveyPublicService $public;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('cms_workspace', DB::connection()->getDatabaseName());
        DB::beginTransaction();
        $this->surveys = app(SurveyService::class);
        $this->career = app(CareerService::class);
        $this->public = app(SurveyPublicService::class);
    }

    /** Quản trị có đủ quyền nghenghiep-* và manifest không chặn (manifest của máy dev thuộc môi trường, không phụ thuộc vào test). */
    private function admin(): User
    {
        config(['cmsbackend.developer.manifest_path' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'no-manifest-'.bin2hex(random_bytes(4)).'.json']);
        app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class)->forget();
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Quản trị'))->firstOrFail();
        $labels = [];
        foreach (\Modules\CmsBackend\Enums\PermissionsEnum::cases() as $case) {
            if (str_starts_with($case->value, 'nghenghiep-')) {
                \Spatie\Permission\Models\Permission::findOrCreate($case->label(), 'web');
                $labels[] = $case->label();
            }
        }
        $admin->givePermissionTo($labels);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    /** Người dùng không có vai trò nào (factory của dự án thiếu cột status nên tạo trực tiếp). */
    private function plainUser(string $tag): User
    {
        return User::query()->create(['name' => 'Test '.$tag, 'username' => 'test-'.$tag.'-'.bin2hex(random_bytes(3)), 'email' => $tag.'-'.bin2hex(random_bytes(3)).'@example.test', 'password' => bcrypt('Zz!pass-'.$tag), 'status' => 1, 'type' => 0]);
    }

    protected function tearDown(): void
    {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function question(string $text, string $letter, string $type = 'scale'): array
    {
        return ['id' => null, 'type' => $type, 'text' => $text, 'description' => null, 'required' => true, 'enabled' => true,
            'config' => ($type === 'scale' ? ['min' => 1, 'max' => 5] : []) + ['riasec' => $letter], 'options' => []];
    }

    /** Khảo sát nghề nghiệp có 2 phần: Xã hội (S) và Nghiên cứu (I, 1 câu Có/Không). */
    private function careerSurvey(string $kind = SurveyService::CAREER): KhaoSat
    {
        $survey = $this->surveys->create([
            'ks_ten' => 'Test career '.bin2hex(random_bytes(3)), 'ks_ma' => 'test-career-'.bin2hex(random_bytes(4)),
            'ks_loai' => $kind, 'ks_cho_phep_an_danh' => true, 'ks_yeu_cau_thong_tin' => false, 'ks_so_lan_gui' => 0,
        ], null);
        $this->surveys->saveStructure($survey, [
            ['id' => null, 'name' => 'Xã hội', 'description' => null, 'questions' => [$this->question('Giúp người khác', 'S'), $this->question('Làm việc nhóm', 'S')]],
            ['id' => null, 'name' => 'Nghiên cứu', 'description' => null, 'questions' => [$this->question('Thích tìm hiểu', 'I', 'yesno')]],
        ], null);

        return $survey->refresh();
    }

    private function ctx(): array
    {
        return ['fingerprint' => bin2hex(random_bytes(16)), 'ip_hash' => bin2hex(random_bytes(8)), 'user_agent' => 'phpunit'];
    }

    /** @return array<string, string> khóa mã câu hỏi => giá trị, theo thứ tự câu hỏi của khảo sát */
    private function answersFor(KhaoSat $survey, array $values): array
    {
        $keys = $survey->nhom()->orderBy('nhom_thu_tu')->get()->flatMap(fn ($g) => $g->cauHoi->pluck('ch_ma'))->values()->all();

        return array_combine($keys, $values);
    }

    private function complete(KhaoSat $survey, array $values, array $identity = []): KhaoSatUser
    {
        $ctx = $this->ctx();
        $participation = $this->public->start($survey, $identity, $ctx);
        $result = $this->public->submit($survey->refresh(), $participation->ksu_ma, $this->answersFor($survey, $values), $ctx);

        return $result['participation']->refresh();
    }

    public function test_builder_requires_a_riasec_group_and_only_scale_or_yes_no_questions(): void
    {
        $survey = $this->careerSurvey();
        $base = $this->surveys->structureForBuilder($survey);

        $noGroup = $base;
        unset($noGroup[0]['questions'][0]['config']['riasec']);
        $this->expectRule(fn () => $this->surveys->saveStructure($survey, $noGroup, null), 'RIASEC');

        $badLetter = $base;
        $badLetter[0]['questions'][0]['config']['riasec'] = 'X';
        $this->expectRule(fn () => $this->surveys->saveStructure($survey, $badLetter, null), 'RIASEC');

        $single = $base;
        $single[0]['questions'][0]['type'] = 'single';
        $single[0]['questions'][0]['options'] = [['id' => null, 'text' => 'A', 'enabled' => true], ['id' => null, 'text' => 'B', 'enabled' => true]];
        $this->expectRule(fn () => $this->surveys->saveStructure($survey, $single, null), 'Thang điểm');

        // Câu hỏi đang tắt không bắt buộc có nhóm.
        $disabled = $base;
        $disabled[0]['questions'][0]['enabled'] = false;
        unset($disabled[0]['questions'][0]['config']['riasec']);
        $this->surveys->saveStructure($survey, $disabled, null);
        $this->assertTrue(true);
    }

    private function expectRule(callable $call, string $messageContains): void
    {
        try {
            $call();
            $this->fail('Expected SurveyRuleException');
        } catch (SurveyRuleException $e) {
            $this->assertStringContainsString($messageContains, $e->getMessage());
        }
    }

    public function test_non_career_survey_drops_riasec_and_keeps_every_question_type(): void
    {
        $survey = $this->careerSurvey('general');
        $structure = $this->surveys->structureForBuilder($survey);

        $this->assertArrayNotHasKey('riasec', (array) $structure[0]['questions'][0]['config']);
        $single = $structure;
        $single[0]['questions'][0]['type'] = 'single';
        $single[0]['questions'][0]['options'] = [['id' => null, 'text' => 'A', 'enabled' => true], ['id' => null, 'text' => 'B', 'enabled' => true]];
        $this->surveys->saveStructure($survey, $single, null);
        $this->assertTrue(true);
    }

    public function test_publish_needs_questions_in_at_least_two_groups(): void
    {
        $survey = $this->careerSurvey();
        $one = $this->surveys->structureForBuilder($survey);
        $one[1]['questions'][0]['config']['riasec'] = 'S';
        $this->surveys->saveStructure($survey, $one, null);
        $this->expectRule(fn () => $this->surveys->transition($survey->refresh(), SurveyStatus::Open, null), 'ít nhất 2 nhóm');

        $two = $this->surveys->structureForBuilder($survey->refresh());
        $two[1]['questions'][0]['config']['riasec'] = 'I';
        $this->surveys->saveStructure($survey, $two, null);
        $this->surveys->transition($survey->refresh(), SurveyStatus::Open, null);
        $this->assertSame(SurveyStatus::Open, $survey->refresh()->status());
    }

    public function test_submit_stores_scores_holland_code_and_school(): void
    {
        $survey = $this->careerSurvey();
        $this->surveys->transition($survey, SurveyStatus::Open, null);
        Option::query()->updateOrCreate(['key' => 'school_list'], ['value' => json_encode([['key' => 'thpt-x', 'name' => 'THPT X']]), 'autoload' => 1]);

        // S: (5 + 3) -> (1.0 + 0.5) / 2 = 75%; I: Không = 0% => SI
        $done = $this->complete($survey->refresh(), ['5', '3', 'no'], ['truong' => 'thpt-x']);

        $this->assertSame('SI', $done->ksu_ket_qua);
        $scores = json_decode((string) $done->ksu_diem, true);
        $this->assertSame(75, $scores['S']['pct']);
        $this->assertSame(0, $scores['I']['pct']);
        $this->assertNull($scores['R']['pct']);
        $this->assertSame('thpt-x', $done->school_key);
        $this->assertSame('THPT X', $done->school_name);

        $other = $this->complete($survey->refresh(), ['1', '1', 'yes'], ['truong' => 'khong-co-trong-danh-sach']);
        $this->assertSame('IS', $other->ksu_ket_qua);
        $this->assertNull($other->school_key);
    }

    public function test_non_career_survey_does_not_get_a_result(): void
    {
        $survey = $this->careerSurvey('general');
        $this->surveys->transition($survey, SurveyStatus::Open, null);
        $done = $this->complete($survey->refresh(), ['5', '3', 'no']);

        $this->assertNull($done->ksu_ket_qua);
        $this->assertNull($done->ksu_diem);
        $this->assertNull($this->public->careerResult($survey, $done->ksu_ma));
    }

    public function test_result_suggests_active_matching_majors_ranked_by_affinity(): void
    {
        CareerMajor::query()->delete();
        $exact = $this->career->saveMajor(null, ['name' => 'Ngành khớp hoàn toàn', 'code' => 'si', 'order' => 20], null);
        $partial = $this->career->saveMajor(null, ['name' => 'Ngành khớp một phần', 'code' => 'S', 'order' => 10], null);
        $none = $this->career->saveMajor(null, ['name' => 'Ngành không liên quan', 'code' => 'RC'], null);
        $hidden = $this->career->saveMajor(null, ['name' => 'Ngành ẩn', 'code' => 'SI', 'active' => false], null);

        $this->assertSame('SI', $exact->cm_ma_riasec);
        $names = $this->career->suggestions('SI')->pluck('cm_ten')->all();
        $this->assertSame(['Ngành khớp hoàn toàn', 'Ngành khớp một phần'], $names);
        $this->assertNotContains($none->cm_ten, $names);
        $this->assertNotContains($hidden->cm_ten, $names);
        $this->assertSame([], $this->career->suggestions(null)->all());
        $this->assertSame([], $this->career->suggestions('')->all());
    }

    public function test_thank_you_page_shows_the_result_or_the_updating_message(): void
    {
        $survey = $this->careerSurvey();
        $this->surveys->transition($survey, SurveyStatus::Open, null);
        CareerMajor::query()->delete();
        $done = $this->complete($survey->refresh(), ['5', '5', 'no']);
        $session = ['survey.done.'.$survey->ks_id => $done->ksu_ma];

        $empty = $this->withSession($session)->get(route('cms.frontend.nghenghiep.thanks', $survey->ks_ma))->assertOk();
        $empty->assertSee('Kết quả định hướng nghề nghiệp')->assertSee('Mã Holland của bạn')->assertSee('Thông tin đang được cập nhật.')
            ->assertDontSee('"riasec"', false);
        $this->assertSame('SI', $done->ksu_ket_qua);

        $this->career->saveMajor(null, ['name' => 'Ngành xã hội thử', 'code' => 'S', 'url' => 'https://example.org/x'], null);
        $this->career->saveMajor(null, ['name' => 'Ngành link xấu', 'code' => 'S', 'url' => 'javascript:alert(1)'], null);
        $page = $this->withSession($session)->get(route('cms.frontend.nghenghiep.thanks', $survey->ks_ma))->assertOk();
        $page->assertSee('Ngành xã hội thử')->assertSee('href="https://example.org/x"', false)->assertDontSee('javascript:alert', false);

        // Không có mã lượt trong phiên: quay lại trang giới thiệu, không lộ kết quả của người khác.
        $this->flushSession();
        $this->get(route('cms.frontend.nghenghiep.thanks', $survey->ks_ma))->assertRedirect(route('cms.frontend.nghenghiep.show', $survey->ks_ma));
    }

    public function test_group_text_can_be_customised_and_falls_back_to_defaults(): void
    {
        CareerGroup::query()->delete();   // độc lập với tên nhóm quản trị đã chỉnh trên database dev (rollback cuối test)
        $default = $this->career->groups()['S']['name'];
        $this->assertSame(__('career.groups.S.name'), $default);

        $this->career->saveGroups(['S' => ['name' => 'Nhóm xã hội của trường', 'description' => 'Mô tả riêng']]);
        $groups = $this->career->groups();
        $this->assertSame('Nhóm xã hội của trường', $groups['S']['name']);
        $this->assertSame('Mô tả riêng', $groups['S']['description']);
        $this->assertSame(__('career.groups.R.name'), $groups['R']['name']);

        $this->career->saveGroups(['S' => ['name' => '', 'description' => '']]);
        $this->assertSame($default, $this->career->groups()['S']['name']);
    }

    public function test_major_validation_rejects_codes_without_valid_letters(): void
    {
        $this->expectRule(fn () => $this->career->saveMajor(null, ['name' => 'X', 'code' => '123'], null), 'RIASEC');
    }

    public function test_admin_pages_open_and_save_majors_through_http(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('cms.nghe-nghiep.catalog'))->assertOk()->assertSee('Sáu nhóm RIASEC')->assertSee('Thêm ngành');

        $this->post(route('cms.nghe-nghiep.catalog.major.store'), ['name' => 'Ngành thử HTTP', 'code' => 'ia', 'order' => 3, 'active' => 1])->assertRedirect(route('cms.nghe-nghiep.catalog'));
        $major = CareerMajor::query()->where('cm_ten', 'Ngành thử HTTP')->firstOrFail();
        $this->assertSame('IA', $major->cm_ma_riasec);
        $this->assertTrue($major->cm_trang_thai);

        $this->post(route('cms.nghe-nghiep.catalog.major.store'), ['name' => 'Sai mã', 'code' => 'zzz'])->assertSessionHasErrors('code');
        $this->post(route('cms.nghe-nghiep.catalog.major.update', $major->cm_id), ['name' => 'Ngành thử HTTP', 'code' => 'I', 'order' => 1])->assertRedirect(route('cms.nghe-nghiep.catalog'));
        $major->refresh();
        $this->assertSame('I', $major->cm_ma_riasec);
        $this->assertFalse($major->cm_trang_thai);   // không gửi "active" nghĩa là ẩn

        $this->post(route('cms.nghe-nghiep.catalog.major.destroy', $major->cm_id))->assertRedirect(route('cms.nghe-nghiep.catalog'));
        $this->assertNull(CareerMajor::query()->find($major->cm_id));
    }

    public function test_survey_pages_are_declared_default_language_only(): void
    {
        // Câu hỏi/ngành do quản trị nhập, chưa có bảng bản dịch: theo quy ước của dự án, trang công khai chỉ có ngôn ngữ mặc định và
        // trên trang tiếng Anh, liên kết tới Khảo sát dẫn tới thông báo "đang cập nhật" (config/locales.php).
        $this->assertContains('cms.frontend.khaosat.', config('locales.default_only_routes'));
        $this->assertContains('cms.frontend.nghenghiep.', config('locales.default_only_routes'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('cms.en.frontend.khaosat.show'));
    }

    public function test_career_screens_need_a_logged_in_user_with_survey_permission(): void
    {
        $this->get(route('cms.nghe-nghiep.catalog'))->assertRedirect(route('cms.login'));
        $this->post(route('cms.nghe-nghiep.catalog.major.store'), ['name' => 'X', 'code' => 'I'])->assertRedirect(route('cms.login'));
    }

    public function test_career_cluster_does_not_depend_on_the_survey_feature(): void
    {
        $admin = $this->admin();
        $survey = $this->careerSurvey();
        $this->surveys->transition($survey, SurveyStatus::Open, null);
        $done = $this->complete($survey->refresh(), ['5', '5', 'yes']);
        $session = ['survey.done.'.$survey->ks_id => $done->ksu_ma];
        $registry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);

        $registry->setEnabled('survey', false, null);
        $this->actingAs($admin)->get(route('cms.nghe-nghiep'))->assertOk();
        $this->get(route('cms.nghe-nghiep.catalog'))->assertOk();
        $this->get(route('cms.khao-sat'))->assertNotFound();
        $this->withSession($session)->get(route('cms.frontend.nghenghiep.thanks', $survey->ks_ma))->assertOk();
        $this->get(route('cms.frontend.khaosat.index'))->assertNotFound();
        $this->assertSame(1, DB::table('ks_khao_sat_users')->where('ksu_id', $done->ksu_id)->count());
        $registry->setEnabled('survey', true, null);
    }

    public function test_english_texts_exist_for_the_admin_screen_and_the_result_partial(): void
    {
        $admin = $this->admin();
        CareerGroup::query()->delete();
        CareerMajor::query()->delete();
        $survey = $this->careerSurvey();
        $this->surveys->transition($survey, SurveyStatus::Open, null);
        $done = $this->complete($survey->refresh(), ['5', '5', 'yes']);

        // Chức năng "Đa ngôn ngữ" (Developer Settings) đang tắt thì quản trị chỉ còn tiếng Việt: bật trong test để kiểm tra bản EN.
        app(\Modules\CmsBackend\Features\FeatureRegistry::class)->setEnabled('multilingual', true, null);
        // Quản trị chọn ngôn ngữ theo session (LocaleMiddleware).
        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('cms.nghe-nghiep.catalog'))->assertOk()
            ->assertSee('Groups &amp; majors (Career orientation)', false)->assertSee('The six RIASEC groups')->assertSee('Add major')
            ->assertDontSee('Sáu nhóm RIASEC')->assertDontSee('Thêm ngành');

        // Trang công khai của Khảo sát chỉ có địa chỉ mặc định (không có /en/khao-sat, xem docs): kiểm tra riêng khối kết quả bằng bản EN.
        app()->setLocale('en');
        $html = view('cmsfrontend::screens.pages.khao-sat._career-result', ['result' => $this->career->resultFor($done)])->render();
        foreach (['Career orientation result', 'Your Holland code', 'Technical – Hands-on (Realistic)', 'Score for each of the 6 groups', 'Information is being updated.'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('Kết quả định hướng', $html);
    }

    public function test_developer_settings_lists_career_and_can_switch_it(): void
    {
        $admin = $this->admin();
        $unlocked = [\Modules\CmsBackend\Services\Developer\DeveloperAccess::SESSION_KEY => true];

        $page = $this->actingAs($admin)->withSession($unlocked)->get(route('cms.dashboard.developer-settings'))->assertOk();
        $page->assertSee('data-feature="career"', false)->assertSee('Định hướng nghề nghiệp (RIASEC)')->assertSee('/cms/dinh-huong-nghe-nghiep');

        $this->post(route('cms.dashboard.developer-settings.features.save', 'career'), ['enabled' => 0])->assertRedirect();
        $this->assertFalse(app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('career'));
        $this->get(route('cms.nghe-nghiep.catalog'))->assertNotFound();
        $this->assertTrue(app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('survey'), 'tắt career không tắt Khảo sát');

        $this->post(route('cms.dashboard.developer-settings.features.save', 'career'), ['enabled' => 1])->assertRedirect();
        $this->assertTrue(app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('career'));
        $this->get(route('cms.nghe-nghiep.catalog'))->assertOk();
    }

    public function test_career_has_its_own_switch_in_the_feature_registry(): void
    {
        $registry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);
        $definition = $registry->find('career');
        $this->assertNotNull($definition, 'chức năng career phải hiện trong Feature Registry (Developer Settings)');
        $this->assertSame('cms.nghe-nghiep', $definition->adminRoute());
        $this->assertContains('nghenghiep-manager', $definition->permissions(), 'quyền riêng nghenghiep-*, không dùng chung khaosat-*');
        $this->assertNotContains('khaosat-view', $definition->permissions());
        $this->assertSame('career', $registry->featureOfPermission('nghenghiep-view'));
        $this->assertSame('survey', $registry->featureOfPermission('khaosat-view'));
        $this->assertSame('cms.frontend.nghenghiep.index', $definition->publicTargets()[0]['route']);
        $this->assertTrue(collect($registry->report())->firstWhere('key', 'career')['routes_ok']);

        $admin = $this->admin();
        $career = $this->careerSurvey();
        $this->surveys->transition($career, SurveyStatus::Open, null);
        $general = $this->careerSurvey('general');
        $this->surveys->transition($general, SurveyStatus::Open, null);
        $done = $this->complete($career->refresh(), ['5', '5', 'yes']);

        $registry->setEnabled('career', false, null);
        $this->actingAs($admin)->get(route('cms.nghe-nghiep.catalog'))->assertNotFound();
        $this->get(route('cms.nghe-nghiep'))->assertNotFound();
        $this->get(route('cms.frontend.nghenghiep.show', $career->ks_ma))->assertNotFound();
        $this->get(route('cms.frontend.nghenghiep.index'))->assertNotFound();
        $this->withSession(['survey.done.'.$career->ks_id => $done->ksu_ma])->get(route('cms.frontend.nghenghiep.thanks', $career->ks_ma))->assertNotFound();
        $this->get(route('cms.frontend.khaosat.show', $general->ks_ma))->assertOk();   // khảo sát thường không bị ảnh hưởng
        $this->get(route('cms.khao-sat'))->assertOk();
        $this->expectRule(fn () => $this->surveys->create(['ks_ten' => 'Mới', 'ks_loai' => SurveyService::CAREER], null), 'Developer Settings');
        // Khảo sát career đã có vẫn sửa thông tin được (giữ nguyên loại) và dữ liệu giữ nguyên.
        $this->surveys->update($career->refresh(), ['ks_ten' => 'Đổi tên', 'ks_loai' => SurveyService::CAREER, 'ks_cho_phep_an_danh' => 1, 'ks_so_lan_gui' => 0], null);
        $this->assertSame(1, DB::table('ks_khao_sat_users')->where('ksu_id', $done->ksu_id)->count());

        $registry->setEnabled('career', true, null);
        $this->get(route('cms.nghe-nghiep.catalog'))->assertOk();
        $this->get(route('cms.frontend.nghenghiep.show', $career->ks_ma))->assertOk();
    }

    public function test_surveys_stay_in_their_own_cluster(): void
    {
        $admin = $this->admin();
        $career = $this->careerSurvey();
        $general = $this->careerSurvey('general');
        $this->surveys->transition($career, SurveyStatus::Open, null);
        $this->surveys->transition($general, SurveyStatus::Open, null);

        // Danh sách quản trị tách theo cụm.
        $this->actingAs($admin)->get(route('cms.nghe-nghiep'))->assertOk()->assertSee($career->ks_ten)->assertDontSee($general->ks_ten);
        $this->get(route('cms.khao-sat'))->assertOk()->assertSee($general->ks_ten)->assertDontSee($career->ks_ten);

        // Mở nhầm cụm bằng GET: chuyển sang cùng màn của cụm đúng; ghi dữ liệu nhầm cụm: 404.
        $this->get(route('cms.khao-sat.edit', $career->ks_id))->assertRedirect(route('cms.nghe-nghiep.edit', $career->ks_id));
        $this->get(route('cms.nghe-nghiep.statistics', $general->ks_id))->assertRedirect(route('cms.khao-sat.statistics', $general->ks_id));
        $this->post(route('cms.khao-sat.update', $career->ks_id), ['ks_ten' => 'x'])->assertNotFound();
        $this->get(route('cms.nghe-nghiep.edit', $career->ks_id))->assertOk();
        $this->get(route('cms.khao-sat.edit', $general->ks_id))->assertOk();

        // Trang công khai tách theo cụm; địa chỉ cũ chuyển sang địa chỉ mới.
        $this->get(route('cms.frontend.nghenghiep.index'))->assertOk()->assertSee($career->ks_ten)->assertDontSee($general->ks_ten);
        $this->get(route('cms.frontend.khaosat.index'))->assertOk()->assertSee($general->ks_ten)->assertDontSee($career->ks_ten);
        $this->get(route('cms.frontend.khaosat.show', $career->ks_ma))->assertRedirect(route('cms.frontend.nghenghiep.show', $career->ks_ma));
        $this->get(route('cms.frontend.nghenghiep.show', $general->ks_ma))->assertRedirect(route('cms.frontend.khaosat.show', $general->ks_ma));
        $this->post(route('cms.frontend.khaosat.start', $career->ks_ma))->assertNotFound();
    }

    public function test_each_cluster_forces_its_own_survey_type(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('cms.nghe-nghiep.store'), ['ks_ten' => 'Bài nghề nghiệp mới', 'ks_loai' => 'general'])->assertRedirect();
        $this->assertSame('career', KhaoSat::query()->where('ks_ten', 'Bài nghề nghiệp mới')->value('ks_loai'), 'cụm nghề nghiệp luôn tạo loại career');

        $this->post(route('cms.khao-sat.store'), ['ks_ten' => 'Khảo sát ép loại', 'ks_loai' => 'career'])->assertSessionHasErrors('ks_loai');
        $this->assertNull(KhaoSat::query()->where('ks_ten', 'Khảo sát ép loại')->first());
        $this->post(route('cms.khao-sat.store'), ['ks_ten' => 'Khảo sát thường', 'ks_loai' => 'feedback'])->assertRedirect();
        $this->assertSame('feedback', KhaoSat::query()->where('ks_ten', 'Khảo sát thường')->value('ks_loai'));
    }

    public function test_career_has_its_own_permissions_separate_from_survey_permissions(): void
    {
        $this->admin();   // chuẩn bị quyền và manifest mở, rồi dùng hai người dùng khác
        $surveyOnly = $this->plainUser('survey');
        \Spatie\Permission\Models\Permission::findOrCreate(\Modules\CmsBackend\Enums\PermissionsEnum::KHAOSAT_MANAGER->label(), 'web');
        $surveyOnly->givePermissionTo(\Modules\CmsBackend\Enums\PermissionsEnum::KHAOSAT_MANAGER->label());
        $viewer = $this->plainUser('viewer');
        $viewer->givePermissionTo(\Modules\CmsBackend\Enums\PermissionsEnum::NGHENGHIEP_VIEW->label());
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Chỉ có quyền khảo sát: vào được Khảo sát nhưng KHÔNG vào được Định hướng nghề nghiệp.
        $this->actingAs($surveyOnly)->get(route('cms.khao-sat'))->assertOk();
        $this->get(route('cms.nghe-nghiep'))->assertForbidden();
        $this->get(route('cms.nghe-nghiep.catalog'))->assertForbidden();

        // Chỉ có quyền xem nghề nghiệp: xem danh sách, không tạo/sửa được và không vào Khảo sát.
        $this->actingAs($viewer)->get(route('cms.nghe-nghiep'))->assertOk();
        $this->get(route('cms.khao-sat'))->assertForbidden();
        $this->get(route('cms.nghe-nghiep.create'))->assertForbidden();
        $this->post(route('cms.nghe-nghiep.catalog.major.store'), ['name' => 'X', 'code' => 'I'])->assertForbidden();
    }

    public function test_career_admin_menu_entry_exists_and_lists_only_career_permissions(): void
    {
        $menu = json_decode((string) file_get_contents(base_path('Modules/CmsBackend/resources/menu/project-verticalMenu.json')), true)['menu'];
        $entry = collect($menu)->firstWhere('slug', 'cms.nghe-nghiep');
        $this->assertNotNull($entry);
        $this->assertSame('/cms/dinh-huong-nghe-nghiep', $entry['url']);
        $this->assertNotContains('khaosat-manager', $entry['roler']);
        $expected = array_values(array_filter(array_map(fn ($c) => $c->value, \Modules\CmsBackend\Enums\PermissionsEnum::cases()), fn ($v) => str_starts_with($v, 'nghenghiep-')));
        $this->assertEqualsCanonicalizing($expected, $entry['roler'], 'menu liệt kê đủ quyền của cụm');
    }

    public function test_developer_settings_separates_languages_from_features_and_ties_permissions_to_features(): void
    {
        $admin = $this->admin();
        $unlocked = [\Modules\CmsBackend\Services\Developer\DeveloperAccess::SESSION_KEY => true];
        $html = $this->actingAs($admin)->withSession($unlocked)->get(route('cms.dashboard.developer-settings'))->assertOk()->getContent();

        // Tab Chức năng: chức năng nghiệp vụ, KHÔNG có Đa ngôn ngữ.
        $features = substr($html, strpos($html, 'id="dev-features-table"'), strpos($html, 'id="dev-pane-languages"') - strpos($html, 'id="dev-features-table"'));
        foreach (['career', 'contact', 'survey'] as $key) {
            $this->assertStringContainsString('data-feature="'.$key.'"', $features);
        }
        $this->assertStringNotContainsString('data-feature="multilingual"', $features);

        // Tab Ngôn ngữ riêng, có công tắc multilingual.
        $this->assertStringContainsString('id="dev-tab-languages"', $html);
        $languages = substr($html, strpos($html, 'id="dev-pane-languages"'));
        $this->assertStringContainsString('features/multilingual', $languages);
        $this->assertStringContainsString('<code>vi</code>', $languages);

        // Ô Quyền của chức năng dẫn sang tab Quản lý phân quyền đã lọc theo chức năng; nhóm quyền mang nhãn chức năng.
        $this->assertStringContainsString('tab=permissions', $features);
        $this->assertStringContainsString('feature=career', $features);
        $this->assertStringContainsString('data-feature="career"', substr($html, 0, strpos($html, 'id="dev-features-table"')), 'nhóm quyền nghenghiep-* mang chức năng career');
        $this->assertStringContainsString('id="dev-feature"', $html);

        // Bật/tắt Đa ngôn ngữ quay lại đúng tab Ngôn ngữ.
        $this->post(route('cms.dashboard.developer-settings.features.save', 'multilingual'), ['enabled' => 1])->assertRedirect(route('cms.dashboard.developer-settings', ['tab' => 'languages']));
        $this->post(route('cms.dashboard.developer-settings.features.save', 'career'), ['enabled' => 1])->assertRedirect(route('cms.dashboard.developer-settings', ['tab' => 'features']));
    }

    public function test_feature_permissions_exist_in_the_database_after_sync(): void
    {
        $this->artisan('features:sync-permissions')->assertSuccessful();
        $registry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);
        foreach ($registry->report() as $row) {
            $this->assertSame([], $row['permissions_missing_db'], 'quyền của chức năng '.$row['key'].' phải có trong bảng permissions');
        }
        $this->artisan('features:sync-permissions', ['--dry-run' => true])->assertSuccessful();
    }
}
