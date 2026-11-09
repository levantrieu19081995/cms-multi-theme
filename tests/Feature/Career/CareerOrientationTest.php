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

        $empty = $this->withSession($session)->get(route('cms.frontend.khaosat.thanks', $survey->ks_ma))->assertOk();
        $empty->assertSee('Kết quả định hướng nghề nghiệp')->assertSee('Mã Holland của bạn')->assertSee('Thông tin đang được cập nhật.')
            ->assertDontSee('"riasec"', false);
        $this->assertSame('SI', $done->ksu_ket_qua);

        $this->career->saveMajor(null, ['name' => 'Ngành xã hội thử', 'code' => 'S', 'url' => 'https://example.org/x'], null);
        $this->career->saveMajor(null, ['name' => 'Ngành link xấu', 'code' => 'S', 'url' => 'javascript:alert(1)'], null);
        $page = $this->withSession($session)->get(route('cms.frontend.khaosat.thanks', $survey->ks_ma))->assertOk();
        $page->assertSee('Ngành xã hội thử')->assertSee('href="https://example.org/x"', false)->assertDontSee('javascript:alert', false);

        // Không có mã lượt trong phiên: quay lại trang giới thiệu, không lộ kết quả của người khác.
        $this->flushSession();
        $this->get(route('cms.frontend.khaosat.thanks', $survey->ks_ma))->assertRedirect(route('cms.frontend.khaosat.show', $survey->ks_ma));
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
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Quản trị'))->firstOrFail();
        $this->actingAs($admin)->get(route('cms.khao-sat.career'))->assertOk()->assertSee('Sáu nhóm RIASEC')->assertSee('Thêm ngành');

        $this->post(route('cms.khao-sat.career.major.store'), ['name' => 'Ngành thử HTTP', 'code' => 'ia', 'order' => 3, 'active' => 1])->assertRedirect(route('cms.khao-sat.career'));
        $major = CareerMajor::query()->where('cm_ten', 'Ngành thử HTTP')->firstOrFail();
        $this->assertSame('IA', $major->cm_ma_riasec);
        $this->assertTrue($major->cm_trang_thai);

        $this->post(route('cms.khao-sat.career.major.store'), ['name' => 'Sai mã', 'code' => 'zzz'])->assertSessionHasErrors('code');
        $this->post(route('cms.khao-sat.career.major.update', $major->cm_id), ['name' => 'Ngành thử HTTP', 'code' => 'I', 'order' => 1])->assertRedirect(route('cms.khao-sat.career'));
        $major->refresh();
        $this->assertSame('I', $major->cm_ma_riasec);
        $this->assertFalse($major->cm_trang_thai);   // không gửi "active" nghĩa là ẩn

        $this->post(route('cms.khao-sat.career.major.destroy', $major->cm_id))->assertRedirect(route('cms.khao-sat.career'));
        $this->assertNull(CareerMajor::query()->find($major->cm_id));
    }

    public function test_career_screens_need_a_logged_in_user_with_survey_permission(): void
    {
        $this->get(route('cms.khao-sat.career'))->assertRedirect(route('cms.login'));
        $this->post(route('cms.khao-sat.career.major.store'), ['name' => 'X', 'code' => 'I'])->assertRedirect(route('cms.login'));
    }
}
