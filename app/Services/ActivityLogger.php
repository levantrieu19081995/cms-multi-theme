<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        string $action,
        string $module,
        ?Model $subject = null,
        array $properties = [],
        ?string $message = null
    ): void {
        $actionText = match ($action) {
            'create' => 'Thêm mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'restore' => 'Phục hồi',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
            'submit_for_approval' => 'Trình duyệt',
            'publish' => 'Xuất bản',
            'draft' => 'Lưu nháp',
            'stop_publishing' => 'Dừng xuất bản',
            default => ucfirst($action),
        };

        $desc = $message ?: ($actionText . ' ' . $module);

        $logger = activity();

        if ($subject) $logger->performedOn($subject);
        if (auth()->check()) $logger->causedBy(auth()->user());
        if (!empty($properties)) $logger->withProperties($properties);

        $logger->log($desc);
    }
}
