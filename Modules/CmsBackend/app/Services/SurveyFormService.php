<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\SurveyDataProvider;
use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsFrontend\Models\CauHoi;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Dựng "lược đồ biểu mẫu" từ cấu trúc khảo sát cho trang công khai và xem trước.
 * Chỉ dùng mã ổn định (UUID) làm khóa; chỉ câu hỏi/lựa chọn đang bật; không có ID tự tăng, ghi chú nội bộ hay thông tin quản trị.
 */
class SurveyFormService
{
    public function __construct(private SurveyDataProvider $data) {}

    /**
     * @return array{sections: array<int, array<string, mixed>>, question_count: int}
     */
    public function schema(KhaoSat $survey): array
    {
        $this->data->withStructure($survey);
        $sections = [];
        $count = 0;
        foreach ($survey->nhom as $nhom) {
            $questions = $this->questions($nhom->cauHoi);
            if ($questions !== []) {
                $sections[] = ['key' => $nhom->nhom_ma, 'title' => $nhom->nhom_ten, 'description' => $nhom->nhom_mo_ta, 'questions' => $questions];
                $count += count($questions);
            }
        }
        $loose = $this->questions($survey->cauHoi);
        if ($loose !== []) {
            $sections[] = ['key' => 'ungrouped', 'title' => null, 'description' => null, 'questions' => $loose];
            $count += count($loose);
        }

        return ['sections' => $sections, 'question_count' => $count];
    }

    /** @param iterable<CauHoi> $questions @return array<int, array<string, mixed>> */
    private function questions(iterable $questions): array
    {
        $out = [];
        foreach ($questions as $q) {
            if (! $q->ch_trang_thai || $q->ch_ma === null) {
                continue;
            }
            $type = $q->kieu();
            $options = [];
            if ($type->hasOptions()) {
                foreach ($q->traLoi as $o) {
                    if ($o->trl_trang_thai && $o->trl_ma !== null) {
                        $options[] = ['key' => $o->trl_ma, 'label' => $o->trl_noidung];
                    }
                }
                if (count($options) < 1) {
                    continue; // câu hỏi lựa chọn không còn lựa chọn nào thì không hiển thị
                }
            }
            $out[] = [
                'key' => $q->ch_ma,
                'type' => $type->value,
                'text' => $q->ch_noidung,
                'description' => $q->ch_mo_ta,
                'required' => (bool) $q->ch_bat_buoc,
                'config' => $type === SurveyQuestionType::Scale ? array_intersect_key($q->ch_cau_hinh ?? ['min' => 1, 'max' => 5], array_flip(['min', 'max', 'min_label', 'max_label'])) : [],
                'options' => $options,
            ];
        }

        return $out;
    }
}
