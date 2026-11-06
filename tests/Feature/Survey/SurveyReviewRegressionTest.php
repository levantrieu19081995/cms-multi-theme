<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Enums\PermissionsEnum as P;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\KhaoSat;

/** Hồi quy cho các lỗi trong CODEX-REVIEW.md (KS-001 … ). */
final class SurveyReviewRegressionTest extends SurveySqliteTestCase
{
    private function draft(array $structure, array $info = []): KhaoSat
    {
        $svc = app(SurveyService::class);
        $survey = $svc->create($info + ['ks_ten' => 'Hồi quy', 'ks_ma' => 'hoi-quy'], 1);
        $svc->saveStructure($survey, $structure, 1);

        return $survey->fresh();
    }

    // ------------------------------------------------------------------ KS-001

    /** @return array<string, array{string}> */
    public static function xssPayloads(): array
    {
        return [
            'entity-obfuscated javascript url' => ['<a href="java&#x73;cript:alert(1)">x</a>'],
            'entity javascript decimal' => ['<a href="&#106;avascript:alert(1)">x</a>'],
            'tab inside scheme' => ["<a href=\"jav\tascript:alert(1)\">x</a>"],
            'data url' => ['<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>'],
            'vbscript url' => ['<a href="vbscript:msgbox(1)">x</a>'],
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'img javascript src' => ['<img src="javascript:alert(1)">'],
            'svg onload' => ['<svg onload=alert(1)><circle/></svg>'],
            'unclosed tags' => ['<p>mở <b>đậm <script>alert(1)</script><a href="javascript:alert(1)'],
            'script split' => ['<scr<script>ipt>alert(1)</scr</script>ipt>'],
            'iframe' => ['<iframe src="//evil.example"></iframe>'],
            'object embed' => ['<object data="x"></object><embed src="x">'],
            'style tag' => ['<style>body{background:url(javascript:alert(1))}</style>'],
            'style attribute' => ['<p style="background:url(javascript:alert(1))">x</p>'],
            'form action' => ['<form action="javascript:alert(1)"><input name="a"></form>'],
            'event on allowed tag' => ['<p onclick="alert(1)" onmouseover=alert(2)>x</p>'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)">'],
            'math/xlink' => ['<math><a xlink:href="javascript:alert(1)">x</a></math>'],
        ];
    }

    private function assertNoExecutableMarkup(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression('/<\s*(script|style|iframe|object|embed|form|svg|math|meta|link|input)\b/i', $html);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');
        foreach ((new \DOMXPath($dom))->query('//*') as $node) {
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $this->assertFalse(str_starts_with($name, 'on'), "thuộc tính sự kiện còn sót: $name");
                $this->assertNotSame('style', $name);
                if (in_array($name, ['href', 'src'], true)) {
                    $value = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($attr->value, ENT_QUOTES | ENT_HTML5)) ?? '';
                    // URL tương đối là vô hại; nếu có scheme thì chỉ http/https/mailto/tel.
                    if (preg_match('/^([a-z][a-z0-9+.'.chr(92).'-]*):/i', $value, $m) === 1) {
                        $this->assertContains(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], "URL không an toàn: {$attr->value}");
                    }
                }
            }
        }
    }

    /** @dataProvider xssPayloads */
    #[\PHPUnit\Framework\Attributes\DataProvider('xssPayloads')]
    public function test_ks001_payload_is_neutralised_on_save_and_on_public_page(string $payload): void
    {
        $survey = $this->draft(
            [['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]],
            ['ks_gioi_thieu' => '<p>Mở đầu</p>'.$payload],
        );
        $this->assertNoExecutableMarkup((string) $survey->ks_gioi_thieu);
        $this->assertStringContainsString('Mở đầu', (string) $survey->ks_gioi_thieu);

        app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        $page = $this->get('/khao-sat/hoi-quy')->assertOk()->getContent();
        $intro = substr($page, (int) strpos($page, 'sv-intro'));
        $intro = substr($intro, 0, (int) strpos($intro, '</article>'));
        $this->assertNoExecutableMarkup($intro);
    }

    /** @dataProvider xssPayloads */
    #[\PHPUnit\Framework\Attributes\DataProvider('xssPayloads')]
    public function test_ks001_legacy_unsafe_value_already_in_database_is_cleaned_at_render(string $payload): void
    {
        $survey = $this->draft([['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]]);
        app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        DB::table('ks_khao_sat')->update(['ks_gioi_thieu' => '<p>Cũ</p>'.$payload]); // ghi thẳng, bỏ qua bộ lọc lúc lưu
        $page = $this->get('/khao-sat/hoi-quy')->assertOk()->getContent();
        $intro = substr($page, (int) strpos($page, 'sv-intro'));
        $intro = substr($intro, 0, (int) strpos($intro, '</article>'));
        $this->assertNoExecutableMarkup($intro);
    }

    public function test_ks001_legitimate_formatting_survives(): void
    {
        $html = '<p>Xin <strong>chào</strong> <em>bạn</em></p><ul><li>Một</li><li>Hai</li></ul><h3>Tiêu đề</h3><blockquote>Trích</blockquote>'
            .'<p><a href="https://example.com/a?b=1&amp;c=2" target="_blank">Liên kết</a> <a href="mailto:a@example.com">Mail</a></p>';
        $survey = $this->draft([['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]], ['ks_gioi_thieu' => $html]);
        $out = (string) $survey->ks_gioi_thieu;
        foreach (['<strong>chào</strong>', '<em>bạn</em>', '<ul>', '<li>Hai</li>', '<h3>Tiêu đề</h3>', '<blockquote>', 'href="https://example.com/a?b=1&amp;c=2"', 'href="mailto:a@example.com"'] as $needle) {
            $this->assertStringContainsString($needle, $out);
        }
        $this->assertStringContainsString('noopener', $out, 'liên kết target=_blank được thêm rel noopener');
    }

    // ------------------------------------------------------------------ KS-002

    public function test_ks002_each_single_permission_reaches_the_list_and_sees_only_its_own_actions(): void
    {
        $survey = $this->draft([['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]]);
        app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        DB::table('options')->insert(['key' => \App\Enums\OptionEnum::WEB_LOGO->value, 'value' => '/logo.png']);
        $id = $survey->ks_id;

        // [quyền, các đoạn PHẢI có, các đoạn KHÔNG ĐƯỢC có]
        $cases = [
            'response-view' => [P::KHAOSAT_RESPONSE_VIEW, ["/cms/khao-sat/{$id}/phan-hoi"], ["/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/thong-ke", "/cms/khao-sat/{$id}/xoa", "/cms/khao-sat/{$id}/trang-thai", "/cms/khao-sat/{$id}/nhan-ban", 'khao-sat/tao-moi']],
            'statistics-view' => [P::KHAOSAT_STATISTICS_VIEW, ["/cms/khao-sat/{$id}/thong-ke"], ["/cms/khao-sat/{$id}/phan-hoi", "/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/xoa", "/cms/khao-sat/{$id}/trang-thai"]],
            'export' => [P::KHAOSAT_EXPORT, ["/cms/khao-sat/{$id}/xuat?type=summary"], ["/cms/khao-sat/{$id}/phan-hoi", "/cms/khao-sat/{$id}/thong-ke", "/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xoa"]],
            'delete' => [P::KHAOSAT_DELETE, [], ["/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/phan-hoi", "/cms/khao-sat/{$id}/trang-thai"]],
            'response-delete' => [P::KHAOSAT_RESPONSE_DELETE, [], ["/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/xoa", "/cms/khao-sat/{$id}/trang-thai"]],
            'publish' => [P::KHAOSAT_PUBLISH, ["/cms/khao-sat/{$id}/trang-thai"], ["/cms/khao-sat/{$id}/chinh-sua", "/cms/khao-sat/{$id}/xuat", "/cms/khao-sat/{$id}/xoa", "/cms/khao-sat/{$id}/phan-hoi"]],
        ];
        foreach ($cases as $name => [$permission, $must, $mustNot]) {
            $this->actingAs($this->user([$permission->label()]));
            $html = $this->get('/cms/khao-sat')->assertOk()->getContent();
            $this->assertStringContainsString('Hồi quy', $html, $name);
            foreach ($must as $needle) {
                $this->assertStringContainsString($needle, $html, "$name phải thấy $needle");
            }
            foreach ($mustNot as $needle) {
                $this->assertStringNotContainsString($needle, $html, "$name không được thấy $needle");
            }
        }
        // quyền delete: thấy nút xóa khi khảo sát không ở trạng thái Mở
        app(SurveyService::class)->transition($survey->fresh(), SurveyStatus::Paused, 1);
        $this->actingAs($this->user([P::KHAOSAT_DELETE->label()]));
        $this->assertStringContainsString("/cms/khao-sat/{$id}/xoa", $this->get('/cms/khao-sat')->assertOk()->getContent());

        // không có quyền khảo sát nào: vẫn 403
        $this->actingAs($this->user([P::DICHVU_VIEW->label()]));
        $this->get('/cms/khao-sat')->assertForbidden();
        // vào được danh sách không mở rộng quyền ghi: người chỉ có response-view vẫn không sửa/xóa/phát hành được
        $this->actingAs($this->user([P::KHAOSAT_RESPONSE_VIEW->label()]));
        $this->get("/cms/khao-sat/{$id}/chinh-sua")->assertForbidden();
        $this->post("/cms/khao-sat/{$id}/xoa")->assertForbidden();
        $this->post("/cms/khao-sat/{$id}/trang-thai", ['status' => 'ended'])->assertForbidden();
    }

    public function test_ks002_every_menu_role_can_reach_the_list_route(): void
    {
        $menu = json_decode((string) file_get_contents(base_path('Modules/CmsBackend/resources/menu/project-verticalMenu.json')), true);
        $item = collect($menu['menu'] ?? $menu)->first(fn ($i) => ($i['slug'] ?? null) === 'cms.khao-sat');
        $this->assertNotNull($item);
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(fn ($r) => $r->getName() === 'cms.khao-sat');
        $labels = collect($route->gatherMiddleware())->first(fn ($m) => is_string($m) && str_starts_with($m, 'cms.check.permission:'));
        $allowed = explode(',', substr($labels, strlen('cms.check.permission:')));
        foreach ($item['roler'] as $slug) {
            $label = P::from($slug)->label();
            // quyền cha được middleware mở rộng thành các quyền con
            $reachable = in_array($label, $allowed, true) || array_intersect(P::parentChildren()[$label] ?? [], $allowed) !== [];
            $this->assertTrue($reachable, "$slug hiện menu nhưng route danh sách không cho vào");
        }
    }

    // ------------------------------------------------------------------ KS-003

    private function structureWith(array $options, bool $questionEnabled = true): array
    {
        return [['name' => 'P', 'questions' => [['type' => 'single', 'text' => 'Chọn', 'enabled' => $questionEnabled, 'options' => $options]]]];
    }

    public function test_ks003_cannot_publish_when_nothing_renders(): void
    {
        $svc = app(SurveyService::class);
        $cases = [
            'all options disabled' => $this->structureWith([['text' => 'A', 'enabled' => false], ['text' => 'B', 'enabled' => false]]),
            'only one option enabled' => $this->structureWith([['text' => 'A', 'enabled' => true], ['text' => 'B', 'enabled' => false]]),
            'question disabled' => $this->structureWith([['text' => 'A'], ['text' => 'B']], questionEnabled: false),
        ];
        foreach ($cases as $label => $structure) {
            DB::table('ks_nhom')->delete();
            DB::table('ks_cau_hoi')->delete();
            DB::table('ks_tra_loi')->delete();
            DB::table('ks_khao_sat')->delete();
            $survey = $this->draft($structure, ['ks_ma' => 'khao-sat-'.md5($label)]);
            try {
                $svc->transition($survey, SurveyStatus::Open, 1);
                $this->fail("$label: phát hành lẽ ra phải bị từ chối");
            } catch (SurveyRuleException $e) {
                $this->assertNotSame('', $e->getMessage(), $label);
            }
            $this->assertSame('draft', DB::table('ks_khao_sat')->value('ks_status'), "$label: trạng thái không đổi");
        }
    }

    public function test_ks003_one_bad_choice_question_blocks_even_if_another_question_is_fine(): void
    {
        $structure = [['name' => 'P', 'questions' => [
            ['type' => 'text', 'text' => 'Ổn'],
            ['type' => 'multiple', 'text' => 'Hỏng', 'options' => [['text' => 'A'], ['text' => 'B', 'enabled' => false]]],
        ]]];
        $this->expectException(SurveyRuleException::class);
        $this->expectExceptionMessage('Hỏng');
        app(SurveyService::class)->transition($this->draft($structure), SurveyStatus::Open, 1);
    }

    public function test_ks003_valid_survey_publishes_and_public_schema_has_questions(): void
    {
        $survey = $this->draft($this->structureWith([['text' => 'A'], ['text' => 'B'], ['text' => 'C', 'enabled' => false]]));
        $survey = app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        $this->assertSame('open', $survey->ks_status);
        $this->assertSame(1, app(\Modules\CmsBackend\Services\SurveyFormService::class)->schema($survey)['question_count']);
        $this->get('/khao-sat/hoi-quy')->assertOk()->assertSee('1 câu hỏi');
    }

    public function test_ks003_error_messages_exist_in_vietnamese(): void
    {
        $this->assertNotSame('admin.survey.error.publish_choice_options', __('admin.survey.error.publish_choice_options', ['question' => 'x'], 'vi'));
        $this->assertNotSame('admin.survey.error.publish_needs_question', __('admin.survey.error.publish_needs_question', [], 'vi'));
    }

    // ------------------------------------------------------------------ KS-008

    /** @return array<string, mixed> ảnh chụp toàn bộ bảng cấu trúc để chứng minh "không ghi dở dang" */
    private function snapshot(): array
    {
        return [
            'g' => DB::table('ks_nhom')->orderBy('nhom_id')->get()->toArray(),
            'q' => DB::table('ks_cau_hoi')->orderBy('ch_id')->get()->toArray(),
            'o' => DB::table('ks_tra_loi')->orderBy('trl_id')->get()->toArray(),
        ];
    }

    private function twoSurveys(): array
    {
        $a = $this->draft([['name' => 'A1', 'questions' => [
            ['type' => 'single', 'text' => 'QA1', 'options' => [['text' => 'a'], ['text' => 'b']]],
            ['type' => 'single', 'text' => 'QA2', 'options' => [['text' => 'c'], ['text' => 'd']]],
        ]]], ['ks_ma' => 'a']);
        $b = $this->draft([['name' => 'B1', 'questions' => [['type' => 'text', 'text' => 'QB1']]]], ['ks_ma' => 'b']);

        return [$a, $b, app(SurveyService::class)->structureForBuilder($a->fresh())];
    }

    public function test_ks008_duplicate_ids_are_rejected_without_partial_write(): void
    {
        [$a, , $st] = $this->twoSurveys();
        $before = $this->snapshot();
        $svc = app(SurveyService::class);

        $dupGroup = [$st[0], $st[0]];
        $dupQuestion = $st;
        $dupQuestion[0]['questions'][1]['id'] = $dupQuestion[0]['questions'][0]['id'];
        $dupOption = $st;
        $dupOption[0]['questions'][0]['options'][1]['id'] = $dupOption[0]['questions'][0]['options'][0]['id'];
        foreach (['group' => $dupGroup, 'question' => $dupQuestion, 'option' => $dupOption] as $label => $payload) {
            try {
                $svc->saveStructure($a->fresh(), $payload, 1);
                $this->fail("ID $label trùng phải bị từ chối");
            } catch (SurveyRuleException $e) {
                $this->assertStringContainsString('lặp', $e->getMessage(), $label);
            }
            $this->assertEquals($before, $this->snapshot(), "$label: không được ghi dở dang");
        }
    }

    public function test_ks008_foreign_ids_are_rejected(): void
    {
        [$a, $b, $st] = $this->twoSurveys();
        $stB = app(SurveyService::class)->structureForBuilder($b->fresh());
        $before = $this->snapshot();
        $svc = app(SurveyService::class);

        $foreignGroup = $st;
        $foreignGroup[0]['id'] = $stB[0]['id'];
        $foreignQuestion = $st;
        $foreignQuestion[0]['questions'][0]['id'] = $stB[0]['questions'][0]['id'];
        $crossOption = $st; // lựa chọn của câu QA2 gắn vào câu QA1
        $crossOption[0]['questions'][0]['options'][0]['id'] = $st[0]['questions'][1]['options'][0]['id'];
        $optionOnNewQuestion = $st;
        $optionOnNewQuestion[0]['questions'][0]['id'] = null;
        $nonNumeric = $st;
        $nonNumeric[0]['questions'][0]['id'] = '1 OR 1=1';
        foreach (['group' => $foreignGroup, 'question' => $foreignQuestion, 'option sang câu khác' => $crossOption, 'option trên câu mới' => $optionOnNewQuestion, 'không phải số' => $nonNumeric] as $label => $payload) {
            try {
                $svc->saveStructure($a->fresh(), $payload, 1);
                $this->fail("ID $label phải bị từ chối");
            } catch (SurveyRuleException) {
                $this->addToAssertionCount(1);
            }
            $this->assertEquals($before, $this->snapshot(), "$label: không đổi");
        }
    }

    public function test_ks008_over_long_and_malformed_fields_are_rejected_not_truncated(): void
    {
        [$a, , $st] = $this->twoSurveys();
        $before = $this->snapshot();
        $svc = app(SurveyService::class);
        $mutations = [
            'tên phần 256' => fn (&$p) => $p[0]['name'] = str_repeat('x', 256),
            'mô tả phần 2001' => fn (&$p) => $p[0]['description'] = str_repeat('x', 2001),
            'câu hỏi 2001' => fn (&$p) => $p[0]['questions'][0]['text'] = str_repeat('x', 2001),
            'gợi ý 1001' => fn (&$p) => $p[0]['questions'][0]['description'] = str_repeat('x', 1001),
            'lựa chọn 501' => fn (&$p) => $p[0]['questions'][0]['options'][0]['text'] = str_repeat('x', 501),
            'tên phần là mảng' => fn (&$p) => $p[0]['name'] = ['x'],
            'câu hỏi là mảng' => fn (&$p) => $p[0]['questions'][0]['text'] = ['x'],
            'kiểu là mảng' => fn (&$p) => $p[0]['questions'][0]['type'] = ['single'],
        ];
        foreach ($mutations as $label => $mutate) {
            $payload = $st;
            $mutate($payload);
            try {
                $svc->saveStructure($a->fresh(), $payload, 1);
                $this->fail("$label phải bị từ chối");
            } catch (SurveyRuleException) {
                $this->addToAssertionCount(1);
            }
            $this->assertEquals($before, $this->snapshot(), "$label: không đổi");
        }
        $scale = [['name' => 'S', 'questions' => [['type' => 'scale', 'text' => 'S', 'config' => ['min' => 1, 'max' => 5, 'min_label' => str_repeat('x', 101)]]]]];
        $this->expectException(SurveyRuleException::class);
        $svc->saveStructure($a->fresh(), $scale, 1);
    }

    public function test_ks008_values_at_exact_limits_are_accepted(): void
    {
        [$a] = $this->twoSurveys();
        app(SurveyService::class)->saveStructure($a->fresh(), [['name' => str_repeat('x', 255), 'description' => str_repeat('y', 2000), 'questions' => [
            ['type' => 'single', 'text' => str_repeat('q', 2000), 'description' => str_repeat('d', 1000), 'options' => [['text' => str_repeat('o', 500)], ['text' => 'b']]],
        ]]], 1);
        $this->assertSame(2000, mb_strlen((string) DB::table('ks_cau_hoi')->where('ks_id', $a->ks_id)->value('ch_noidung')));
    }

    public function test_ks008_raw_http_request_without_javascript_is_validated(): void
    {
        [$a, , $st] = $this->twoSurveys();
        $before = $this->snapshot();
        $this->withErrorPageSupport();
        $this->actingAs($this->user([P::KHAOSAT_UPDATE->label()]));
        $bad = [$st[0], $st[0]];
        $this->post("/cms/khao-sat/{$a->ks_id}/cau-hoi", ['structure' => json_encode($bad)])->assertRedirect()->assertSessionHasErrors('structure');
        $this->post("/cms/khao-sat/{$a->ks_id}/cau-hoi", ['structure' => '{"không phải mảng cấu trúc'])->assertRedirect()->assertSessionHasErrors('structure');
        $this->assertEquals($before, $this->snapshot());
    }

    // ------------------------------------------------------------------ KS-004

    public function test_ks004_survey_row_is_locked_inside_the_transaction_before_the_limit_is_checked(): void
    {
        $survey = $this->draft([['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]], ['ks_so_lan_gui' => 1]);
        $survey = app(SurveyService::class)->transition($survey, SurveyStatus::Open, 1);
        $key = DB::table('ks_cau_hoi')->value('ch_ma');

        $log = new \ArrayObject;
        $this->app->bind(\Modules\CmsBackend\DataProviders\SurveyResponseDataProvider::class, fn () => new class($log) extends \Modules\CmsBackend\DataProviders\SurveyResponseDataProvider
        {
            public function __construct(private \ArrayObject $log) {}

            public function lockSurvey(int $surveyId): ?KhaoSat
            {
                $this->log[] = 'lock@tx'.DB::transactionLevel();

                return parent::lockSurvey($surveyId);
            }

            public function findParticipationByCode(int $surveyId, string $code, bool $lock = false): ?\Modules\CmsFrontend\Models\KhaoSatUser
            {
                $this->log[] = 'find';

                return parent::findParticipationByCode($surveyId, $code, $lock);
            }

            public function completedCountByFingerprint(int $surveyId, string $fingerprint): int
            {
                $this->log[] = 'count';

                return parent::completedCountByFingerprint($surveyId, $fingerprint);
            }

            public function insertAnswers(\Modules\CmsFrontend\Models\KhaoSatUser $participation, array $rows): void
            {
                $this->log[] = 'insert';
                parent::insertAnswers($participation, $rows);
            }
        });

        $public = app(\Modules\CmsFrontend\Services\SurveyPublicService::class);
        $ctx = ['token' => 'T', 'fingerprint' => hash('sha256', 'T'), 'ip_hash' => 'x', 'user_agent' => null];
        $p = $public->start($survey->fresh(), [], $ctx);
        $this->assertSame(['lock@tx1', 'count'], array_slice($log->getArrayCopy(), 0, 2), 'start: khóa rồi mới đếm, trong transaction');

        $log->exchangeArray([]);
        $public->submit($survey->fresh(), $p->ksu_ma, [$key => 'ý kiến'], $ctx);
        $this->assertSame(['lock@tx1', 'find', 'count', 'insert'], $log->getArrayCopy(), 'submit: khóa khảo sát → khóa lượt → đếm giới hạn → ghi');

        // sau lượt thứ nhất, giới hạn 1 chặn lượt kế tiếp của cùng trình duyệt
        $this->expectException(SurveyRuleException::class);
        $public->start($survey->fresh(), [], $ctx);
    }

    // ------------------------------------------------------------------ KS-009

    public function test_ks009_sensitive_actions_are_audited_without_personal_data(): void
    {
        $records = [];
        \Illuminate\Support\Facades\Log::shouldReceive('info')->andReturnUsing(function ($message, $context = []) use (&$records) {
            if ($message === 'survey.audit') {
                $records[] = $context;
            }
        });
        \Illuminate\Support\Facades\Log::shouldReceive('warning', 'error', 'debug', 'notice')->zeroOrMoreTimes();

        $svc = app(SurveyService::class);
        $survey = $this->draft([['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]], ['ks_yeu_cau_thong_tin' => true, 'ks_cho_phep_an_danh' => false]);
        $survey = $svc->transition($survey, SurveyStatus::Open, 1);

        $public = app(\Modules\CmsFrontend\Services\SurveyPublicService::class);
        $ctx = ['token' => 'T', 'fingerprint' => hash('sha256', 'T'), 'ip_hash' => 'x', 'user_agent' => null];
        $p = $public->start($survey->fresh(), ['ho_ten' => 'Nguyễn Bí Mật', 'email' => 'bi.mat@example.com'], $ctx);
        $public->submit($survey->fresh(), $p->ksu_ma, [DB::table('ks_cau_hoi')->value('ch_ma') => 'câu trả lời riêng tư'], $ctx);

        $this->actingAs($this->user([]));
        app(\Modules\CmsBackend\Services\SurveyReportService::class)->deleteParticipation(\Modules\CmsFrontend\Models\KhaoSatUser::query()->firstOrFail());
        $svc->transition($survey->fresh(), SurveyStatus::Paused, 1);
        $copy = $svc->duplicate($survey->fresh(), 1);
        $svc->delete($survey->fresh());
        $svc->restore(KhaoSat::withTrashed()->findOrFail($survey->ks_id));

        $actions = array_column($records, 'action');
        foreach (['create', 'update_structure', 'status', 'response_delete', 'duplicate', 'delete', 'restore'] as $expected) {
            $this->assertContains($expected, $actions, "thiếu audit cho $expected");
        }
        $delete = collect($records)->firstWhere('action', 'response_delete');
        $this->assertSame($survey->ks_id, $delete['survey_id']);
        $this->assertNotNull($delete['response_id']);
        $this->assertArrayHasKey('user_id', $delete);
        $this->assertArrayHasKey('at', $delete);
        $statusRecord = collect($records)->firstWhere('action', 'status');
        $this->assertSame(['from' => 'draft', 'to' => 'open'], $statusRecord['meta']);
        $this->assertSame($copy->ks_id, collect($records)->firstWhere('action', 'duplicate')['meta']['copy_id']);

        $dump = json_encode($records, JSON_UNESCAPED_UNICODE);
        foreach (['Nguyễn Bí Mật', 'bi.mat@example.com', 'câu trả lời riêng tư'] as $secret) {
            $this->assertStringNotContainsString($secret, $dump, 'nhật ký không được chứa dữ liệu cá nhân/câu trả lời');
        }
    }
}
