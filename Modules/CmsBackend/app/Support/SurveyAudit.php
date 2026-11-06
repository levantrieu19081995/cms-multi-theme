<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Nhật ký thao tác nhạy cảm của Khảo sát: ai, làm gì, trên khảo sát/phản hồi nào, lúc nào.
 * TUYỆT ĐỐI không ghi nội dung câu trả lời, họ tên, liên hệ hay bất kỳ dữ liệu cá nhân nào — chỉ mã định danh và siêu dữ liệu.
 */
final class SurveyAudit
{
    /** @param array<string, scalar|null> $meta chỉ siêu dữ liệu (trạng thái cũ/mới, số lượng…) */
    public static function record(string $action, int $surveyId, ?int $responseId = null, array $meta = []): void
    {
        Log::info('survey.audit', [
            'action' => $action,
            'survey_id' => $surveyId,
            'response_id' => $responseId,
            'user_id' => Auth::id(),
            'at' => now()->toIso8601String(),
            'meta' => $meta,
        ]);
    }
}
