<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\UserTermPermission;

class UserTermPermissionDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new UserTermPermission());
    }

    public static function getTermIdsByUserId(int $user_id): array
{
    return UserTermPermission::where('user_id', $user_id)
                ->where('can_create', 1)
                ->pluck('term_id')
                ->toArray();
}

    public function addUserTermPermission(int $user_id, array $term_ids): void
{
    $now = now();

    $existing = UserTermPermission::where('user_id', $user_id)->get();

    // Chuẩn bị mảng để kiểm tra nhanh
    $existingMap = $existing->keyBy('term_id'); // Map theo term_id
    $incomingMap = array_flip($term_ids); // Map incoming term_ids để tra nhanh

    $insertData = [];
    $updateToOne = [];
    $updateToZero = [];

    foreach ($term_ids as $term_id) {
        if (isset($existingMap[$term_id])) {
            // Đã có → cần đảm bảo can_create = 1
            if (!$existingMap[$term_id]->can_create) {
                $updateToOne[] = $term_id;
            }
        } else {
            // Chưa có → chèn mới
            $insertData[] = [
                'user_id' => $user_id,
                'term_id' => $term_id,
                'can_create' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }

    // Những term_id có sẵn nhưng KHÔNG nằm trong danh sách truyền vào → set can_create = 0
    foreach ($existing as $record) {
        if (!isset($incomingMap[$record->term_id]) && $record->can_create) {
            $updateToZero[] = $record->term_id;
        }
    }

    if (!empty($insertData)) {
        UserTermPermission::insert($insertData);
    }

    if (!empty($updateToOne)) {
        UserTermPermission::where('user_id', $user_id)
            ->whereIn('term_id', $updateToOne)
            ->update(['can_create' => 1, 'updated_at' => $now]);
    }

    if (!empty($updateToZero)) {
        UserTermPermission::where('user_id', $user_id)
            ->whereIn('term_id', $updateToZero)
            ->update(['can_create' => 0, 'updated_at' => $now]);
    }
}

}
