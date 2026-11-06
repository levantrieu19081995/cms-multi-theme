<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

/**
 * Áp hiệu ứng Developer chọn cho một cụm vào HTML đã render của cụm, KHÔNG bọc thêm phần tử và không đổi class/ID. Hiệu ứng dùng chung chạy theo
 * thuộc tính HTML (`effects/EFFECTS.md`), nên việc chọn hiệu ứng chỉ là gỡ/gắn đúng các thuộc tính đó:
 *  - chưa chọn (null): giữ nguyên HTML của partial, tức hiệu ứng hiện tại của theme;
 *  - `none`: gỡ thuộc tính của reveal/stagger (cụm hiện ngay, không hiệu ứng);
 *  - `reveal`: gỡ rồi gắn `data-reveal-lines` vào tiêu đề đầu tiên (h1-h3) của cụm: chữ hiện theo dòng khi cuộn tới;
 *  - `stagger`: gỡ rồi gắn nhóm trồi lên lần lượt (`data-stagger-group=":scope > *"`) vào phần tử gốc của cụm.
 * Hiệu ứng gắn cấu trúc (slider banner, vòng ảnh, đếm số) không chọn được ở đây vì cần HTML riêng; chúng giữ nguyên như partial.
 */
final class BlockEffectApplier
{
    private const HOOKS = '/\sdata-(?:reveal-lines|stagger-group|stagger-offset)(?:=(?:"[^"]*"|\'[^\']*\'))?(?=[\s>\/])/';

    public static function apply(string $html, ?string $choice): string
    {
        if ($choice === null || $choice === '' || $html === '') {
            return $html;
        }
        $html = (string) preg_replace(self::HOOKS, '', $html);

        return match ($choice) {
            'reveal' => self::addToFirst($html, '/<h[1-3]\b(?![^>]*\bdata-reveal-lines)/i', ' data-reveal-lines'),
            'stagger' => self::addToFirst($html, '/<(?!\/|!)[a-z][a-z0-9-]*\b/i', ' data-stagger-group=":scope > *"'),
            default => $html,
        };
    }

    /** Chèn thuộc tính ngay sau tên thẻ của kết quả khớp đầu tiên; không khớp thì giữ nguyên. */
    private static function addToFirst(string $html, string $pattern, string $attribute): string
    {
        return (string) preg_replace_callback($pattern, fn (array $match) => $match[0].$attribute, $html, 1);
    }
}
