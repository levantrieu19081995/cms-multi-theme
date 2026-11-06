<?php

namespace Modules\CmsBackend\Support;

final class EditorialPermissions
{
    public const AUTHOR_ROLE = 'Khuyến nông – Tác giả soạn tin';

    public const EDITOR_ROLE = 'Khuyến nông – Biên tập viên';

    public static function label(string $type, string $action): string
    {
        $subject = $type === 'event' ? 'sự kiện' : 'tin tức';
        $verb = ['view' => 'Xem', 'create' => 'Tạo', 'update' => 'Biên tập', 'approve' => 'Phê duyệt', 'publish' => 'Đăng', 'delete' => 'Xóa'][$action];

        return "Khuyến nông: {$verb} {$subject}";
    }

    public static function definitions(): array
    {
        $items = [];
        foreach (['post', 'event'] as $type) {
            $parent = $type === 'event'
                ? \Modules\CmsBackend\Enums\PermissionsEnum::SUKIEN_MANAGER->label()
                : \Modules\CmsBackend\Enums\PermissionsEnum::BAIVIET_MANAGER->label();

            foreach (['view', 'create', 'update', 'approve', 'publish', 'delete'] as $action) {
                $items[] = [
                    'name' => self::label($type, $action),
                    'slug' => "cms-{$type}-{$action}",
                    'type' => $type,
                    'action' => $action,
                    'parent' => $parent,
                ];
            }
        }

        return $items;
    }
}
