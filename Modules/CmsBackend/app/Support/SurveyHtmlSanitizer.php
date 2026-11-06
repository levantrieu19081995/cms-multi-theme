<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Lọc HTML do quản trị nhập (nội dung giới thiệu khảo sát) bằng HTMLPurifier với danh sách cho phép (allow-list).
 * HTMLPurifier được cài sẵn trong vendor (phụ thuộc gián tiếp). Không dùng regex để lọc bảo mật.
 *
 * Cho phép: định dạng văn bản cơ bản, danh sách, tiêu đề, trích dẫn, liên kết http/https/mailto/tel, ảnh http/https.
 * Loại bỏ: script, style, iframe, object, embed, form, svg, thuộc tính sự kiện, javascript:/data:/vbscript:, HTML lỗi được dựng lại cho cân bằng thẻ.
 */
final class SurveyHtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }
        $clean = trim($this->purifier()->purify($html));

        return $clean === '' ? null : $clean;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }
        $cache = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ul,ol,li,h2,h3,h4,blockquote,hr,span,a[href|title|target],img[src|alt|width|height]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetNoopener', true);
        $config->set('HTML.TargetNoreferrer', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        if (is_dir($cache) && is_writable($cache)) {
            $config->set('Cache.SerializerPath', $cache);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }

        return $this->purifier = new HTMLPurifier($config);
    }
}
