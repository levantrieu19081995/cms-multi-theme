<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use Tests\TestCase;

/** KS-006: cây khóa VI/EN khớp nhau và không có chữ tiếng Việt viết cứng trong Blade/JS của module Khảo sát. */
final class SurveyI18nTest extends TestCase
{
    /** @return array<int, string> */
    private function flatten(array $tree, string $prefix = ''): array
    {
        $keys = [];
        foreach ($tree as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                array_push($keys, ...$this->flatten($value, $path));
            } else {
                $keys[] = $path;
            }
        }
        sort($keys);

        return $keys;
    }

    private function lang(string $locale, string $file): array
    {
        return require base_path("lang/{$locale}/{$file}.php");
    }

    public function test_admin_survey_trees_have_identical_keys_in_vi_and_en(): void
    {
        $vi = $this->lang('vi', 'admin');
        $en = $this->lang('en', 'admin');
        foreach (['survey', 'survey_report'] as $group) {
            $this->assertNotEmpty($en[$group] ?? null, "thiếu nhóm admin.$group trong lang/en/admin.php");
            $this->assertSame($this->flatten($vi[$group]), $this->flatten($en[$group]), "admin.$group lệch khóa giữa VI và EN");
        }
        foreach (['feature_blocked', 'feature_blocked_hint'] as $key) {
            $this->assertArrayHasKey($key, $en['developer']);
        }
        $this->assertArrayHasKey('quan_ly_khao_sat', $en['menu']);
    }

    public function test_public_survey_trees_have_identical_keys_in_vi_and_en(): void
    {
        $this->assertSame($this->flatten($this->lang('vi', 'survey')), $this->flatten($this->lang('en', 'survey')));
    }

    public function test_no_value_is_empty_or_a_vietnamese_copy_in_the_english_tree(): void
    {
        $en = $this->lang('en', 'admin');
        $diacritics = '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu';
        foreach (['survey', 'survey_report'] as $group) {
            foreach ($this->flatten($en[$group]) as $path) {
                $value = data_get($en[$group], $path);
                $this->assertNotSame('', trim((string) $value), "$group.$path rỗng");
                $this->assertDoesNotMatchRegularExpression($diacritics, (string) $value, "$group.$path còn tiếng Việt trong bản EN");
            }
        }
    }

    public function test_english_locale_renders_translated_messages(): void
    {
        app()->setLocale('en');
        $this->assertSame('Open', __('admin.survey.status.open'));
        $this->assertStringContainsString('at least 2 enabled options', __('admin.survey.error.publish_choice_options', ['question' => 'Q']));
        $this->assertSame('Survey responses', __('admin.survey_report.responses_title'));
        $this->assertSame('Start the survey', __('survey.intro.start'));
    }

    public function test_no_hardcoded_vietnamese_in_survey_blade_and_js(): void
    {
        $diacritics = '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu';
        $files = array_merge(
            glob(base_path('Modules/CmsBackend/resources/views/screens/default/pages/khao-sat/*.blade.php')),
            glob(base_path('Modules/CmsFrontend/resources/views/screens/*/pages/khao-sat/*.blade.php')),
            [base_path('public/assets/js/survey/builder.js')],
        );
        $this->assertGreaterThan(10, count($files));
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            $code = preg_replace('/\{\{--.*?--\}\}/s', '', $code) ?? '';        // chú thích Blade
            $code = preg_replace('#/\*.*?\*/#s', '', $code) ?? '';               // chú thích khối
            $code = preg_replace('#(?<![:"\'])//[^\n]*#', '', $code) ?? '';      // chú thích dòng JS
            $this->assertDoesNotMatchRegularExpression($diacritics, $code, basename($file).' chứa chữ tiếng Việt viết cứng (phải dùng __() / cấu hình i18n)');
        }
    }
}
