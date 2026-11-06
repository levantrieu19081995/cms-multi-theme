<?php

namespace Modules\CmsBackend\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\CmsBackend\DataProviders\SurveyDataProvider;
use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Support\SurveyAudit;
use Modules\CmsBackend\Support\SurveyHtmlSanitizer;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Nghiệp vụ quản trị khảo sát: thông tin chung, trình dựng câu hỏi, trạng thái, nhân bản, xóa an toàn.
 * Quy tắc bảo vệ dữ liệu (DECISIONS D7, D8) được thực thi ở đây, không chỉ ở giao diện.
 */
class SurveyService
{
    public const MAX_GROUPS = 30;

    public const MAX_QUESTIONS = 300;

    public const MAX_OPTIONS = 50;

    public function __construct(private SurveyDataProvider $data, private SurveyFormService $forms, private SurveyHtmlSanitizer $sanitizer) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->data->paginateSurveys($filters);
    }

    public function find(int $id, bool $withTrashed = false): KhaoSat
    {
        return $this->data->find($id, $withTrashed) ?? throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(KhaoSat::class, [$id]);
    }

    public function withStructure(KhaoSat $survey): KhaoSat
    {
        return $this->data->withStructure($survey);
    }

    // ------------------------------------------------------------------ thông tin chung

    /** @param array<string, mixed> $info dữ liệu đã qua FormRequest */
    public function create(array $info, ?int $userId): KhaoSat
    {
        return $this->data->transaction(function () use ($info, $userId) {
            $attributes = $this->infoAttributes($info);
            $attributes['ks_ma'] = $this->uniqueSlug($attributes['ks_ma'] ?: $attributes['ks_ten']);
            $attributes += ['ks_status' => SurveyStatus::Draft->value, 'ks_trang_thai' => false, 'ks_created_by' => $userId, 'ks_updated_by' => $userId];

            $survey = $this->data->create($attributes);
            SurveyAudit::record('create', $survey->ks_id);

            return $survey;
        });
    }

    public function update(KhaoSat $survey, array $info, ?int $userId): KhaoSat
    {
        if (! $survey->status()->allowsInfoEdit()) {
            throw SurveyRuleException::key('ended_read_only');
        }
        $attributes = $this->infoAttributes($info);
        $this->guardCollectionRules($survey, $attributes);
        // Địa chỉ công khai chỉ đổi được khi còn là Nháp (đã phát hành thì liên kết không được vỡ).
        if ($survey->status() === SurveyStatus::Draft && $attributes['ks_ma']) {
            $attributes['ks_ma'] = $this->uniqueSlug($attributes['ks_ma'], $survey->ks_id);
        } else {
            unset($attributes['ks_ma']);
        }
        $attributes['ks_updated_by'] = $userId;
        $updated = $this->data->update($survey, $attributes);
        SurveyAudit::record('update_info', $survey->ks_id);

        return $updated;
    }

    /** Các trường quyết định CÁCH thu thập dữ liệu: khóa sau khi rời Nháp hoặc khi đã có lượt tham gia (KS-007). */
    public const COLLECTION_FIELDS = ['ks_loai', 'ks_bat_dau', 'ks_cho_phep_an_danh', 'ks_yeu_cau_thong_tin', 'ks_so_lan_gui', 'ks_ma'];

    /** Quy tắc thu thập đã bị khóa? (đã phát hành/tạm dừng/kết thúc, hoặc đã có ít nhất một lượt tham gia) */
    public function collectionLocked(KhaoSat $survey): bool
    {
        return $survey->exists
            && ($survey->status() !== SurveyStatus::Draft || $this->data->participationCount($survey->ks_id) > 0);
    }

    /**
     * Chặn ở server mọi thay đổi quy tắc thu thập khi đã khóa (kể cả request tạo thủ công, bỏ qua giao diện) và buộc ngày kết
     * thúc mới của khảo sát đang chạy phải ở tương lai. Chạy TRƯỚC khi ghi nên từ chối thì dữ liệu giữ nguyên.
     *
     * @param  array<string, mixed>  $attributes  thuộc tính đã chuẩn hóa từ infoAttributes()
     */
    private function guardCollectionRules(KhaoSat $survey, array $attributes): void
    {
        $status = $survey->status();
        if ($this->collectionLocked($survey)) {
            $current = [
                'ks_loai' => $survey->ks_loai ?: 'general',
                'ks_bat_dau' => $survey->ks_bat_dau?->format('Y-m-d H:i'),
                'ks_cho_phep_an_danh' => (bool) $survey->ks_cho_phep_an_danh,
                'ks_yeu_cau_thong_tin' => (bool) $survey->ks_yeu_cau_thong_tin,
                'ks_so_lan_gui' => (int) $survey->ks_so_lan_gui,
                // Trường địa chỉ để trống nghĩa là "không gửi" (ô bị vô hiệu hóa) nên coi như giữ nguyên.
                'ks_ma' => $attributes['ks_ma'] === null ? null : (string) $survey->ks_ma,
            ];
            $incoming = [
                'ks_loai' => $attributes['ks_loai'] ?: 'general',
                'ks_bat_dau' => $attributes['ks_bat_dau']?->format('Y-m-d H:i'),
                'ks_cho_phep_an_danh' => (bool) $attributes['ks_cho_phep_an_danh'],
                'ks_yeu_cau_thong_tin' => (bool) $attributes['ks_yeu_cau_thong_tin'],
                'ks_so_lan_gui' => (int) $attributes['ks_so_lan_gui'],
                'ks_ma' => $attributes['ks_ma'],
            ];
            $changed = array_keys(array_diff_assoc(array_map('json_encode', $incoming), array_map('json_encode', $current)));
            if ($changed !== []) {
                throw SurveyRuleException::key('collection_locked', [
                    'fields' => implode(', ', array_map(fn (string $f) => __('admin.survey.locked_field.'.$f), $changed)),
                ]);
            }
        }
        $end = $attributes['ks_ket_thuc'];
        if (in_array($status, [SurveyStatus::Open, SurveyStatus::Paused], true) && $end !== null) {
            $before = $survey->ks_ket_thuc?->format('Y-m-d H:i');
            if ($end->format('Y-m-d H:i') !== $before && $end->lessThanOrEqualTo(now())) {
                throw SurveyRuleException::key('end_in_past');
            }
        }
    }

    /** @return array<string, mixed> */
    private function infoAttributes(array $info): array
    {
        $start = ! empty($info['ks_bat_dau']) ? \Carbon\Carbon::parse($info['ks_bat_dau']) : null;
        $end = ! empty($info['ks_ket_thuc']) ? \Carbon\Carbon::parse($info['ks_ket_thuc']) : null;
        if ($start && $end && $end->lessThanOrEqualTo($start)) {
            throw SurveyRuleException::key('end_before_start');
        }

        return [
            'ks_ten' => trim((string) $info['ks_ten']),
            'ks_ma' => ! empty($info['ks_ma']) ? Str::slug((string) $info['ks_ma']) : null,
            'ks_mo_ta_ngan' => $info['ks_mo_ta_ngan'] ?? null,
            'ks_gioi_thieu' => $this->cleanHtml($info['ks_gioi_thieu'] ?? null),
            'ks_loai' => $info['ks_loai'] ?? 'general',
            'ks_bat_dau' => $start,
            'ks_ket_thuc' => $end,
            'ks_cho_phep_an_danh' => (bool) ($info['ks_cho_phep_an_danh'] ?? false),
            'ks_yeu_cau_thong_tin' => (bool) ($info['ks_yeu_cau_thong_tin'] ?? false),
            'ks_so_lan_gui' => (int) ($info['ks_so_lan_gui'] ?? 1),
            'ks_noi_dung_hoan_thanh' => $info['ks_noi_dung_hoan_thanh'] ?? null,
            'ks_ghichu' => $info['ks_ghichu'] ?? null,
        ];
    }

    /** Nội dung giới thiệu là HTML do quản trị nhập và hiển thị công khai: lọc bằng allow-list (HTMLPurifier), không dùng regex. */
    private function cleanHtml(?string $html): ?string
    {
        return $this->sanitizer->clean($html);
    }

    private function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = Str::slug($base) ?: 'khao-sat';
        $slug = Str::limit($slug, 100, '');
        $candidate = $slug;
        for ($i = 2; $this->data->slugExists($candidate, $exceptId); $i++) {
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
    }

    // ------------------------------------------------------------------ trình dựng câu hỏi

    /**
     * Lưu cấu trúc nhóm/câu hỏi/lựa chọn. Chỉ khi khảo sát là Nháp; các trạng thái khác dùng Nhân bản.
     *
     * @param  array<int, array<string, mixed>>  $groups
     */
    public function saveStructure(KhaoSat $survey, array $groups, ?int $userId): void
    {
        $status = $survey->status();
        if (! $status->allowsStructureEdit()) {
            throw SurveyRuleException::key($status === SurveyStatus::Ended ? 'ended_read_only' : 'structure_locked');
        }
        // Kiểm tra TOÀN BỘ payload trước khi ghi: lỗi nào cũng dừng ở đây, không ghi dở dang.
        $groups = $this->normalizeStructure($groups, $survey);
        $this->data->transaction(function () use ($survey, $groups, $userId) {
            $this->data->persistStructure($survey, $groups);
            $this->data->update($survey, ['ks_updated_by' => $userId]);
            SurveyAudit::record('update_structure', $survey->ks_id, null, ['groups' => count($groups), 'questions' => array_sum(array_map(fn ($g) => count($g['questions']), $groups))]);
        });
    }

    public const MAX_GROUP_NAME = 255;

    public const MAX_GROUP_DESC = 2000;

    public const MAX_QUESTION_TEXT = 2000;

    public const MAX_QUESTION_DESC = 1000;

    public const MAX_OPTION_TEXT = 500;

    public const MAX_SCALE_LABEL = 100;

    /**
     * Chuẩn hóa + kiểm tra cấu trúc. Ném SurveyRuleException với thông báo cụ thể (phần/câu nào sai).
     * Với $survey: mọi ID phải thuộc đúng khảo sát này (lựa chọn thuộc đúng câu hỏi) và không lặp trong payload.
     *
     * @param  array<int, mixed>  $groups
     * @return array<int, array<string, mixed>>
     */
    public function normalizeStructure(array $groups, ?KhaoSat $survey = null): array
    {
        $groups = $this->listOfArrays($groups);
        if (count($groups) > self::MAX_GROUPS) {
            throw SurveyRuleException::key('too_many_groups', ['max' => self::MAX_GROUPS]);
        }
        $known = $survey?->exists ? $this->data->structureIds($survey->ks_id) : null;
        $seen = ['g' => [], 'q' => [], 'o' => []];
        $total = 0;
        $out = [];
        foreach ($groups as $gi => $group) {
            $g = $gi + 1;
            $name = $this->text($group['name'] ?? '', self::MAX_GROUP_NAME, 'group_name', ['g' => $g, 'n' => '—']);
            if ($name === '') {
                throw SurveyRuleException::key('group_name_required', ['n' => $g]);
            }
            $groupId = $this->id($group['id'] ?? null, 'g', ['g' => $g, 'n' => '—'], $seen, $known['groups'] ?? null);
            $questions = [];
            foreach ($this->listOfArrays($group['questions'] ?? []) as $qi => $q) {
                $total++;
                $questions[] = $this->normalizeQuestion($q, $g, $qi + 1, $seen, $known);
            }
            $out[] = [
                'id' => $groupId,
                'name' => $name,
                'description' => $this->nullIfBlank($this->text($group['description'] ?? '', self::MAX_GROUP_DESC, 'group_description', ['g' => $g, 'n' => '—'])),
                'questions' => $questions,
            ];
        }
        if ($total > self::MAX_QUESTIONS) {
            throw SurveyRuleException::key('too_many_questions', ['max' => self::MAX_QUESTIONS]);
        }

        return $out;
    }

    /**
     * Container group/câu hỏi/lựa chọn phải là danh sách các mảng. Sai kiểu thì TỪ CHỐI (không lọc bỏ âm thầm), nếu không
     * payload như ["chuỗi"] sẽ thành cấu trúc rỗng và xóa cấu trúc đang có.
     *
     * @return array<int, array<string, mixed>>
     */
    private function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            throw SurveyRuleException::key('structure_invalid');
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                throw SurveyRuleException::key('structure_invalid');
            }
        }

        return array_values($value);
    }

    /**
     * @param  array{g: array<int, true>, q: array<int, true>, o: array<int, true>}  $seen
     * @param  ?array{groups: array<int, true>, questions: array<int, array<int, true>>}  $known
     * @return array<string, mixed>
     */
    private function normalizeQuestion(array $q, int $g, int $n, array &$seen, ?array $known): array
    {
        $at = ['g' => $g, 'n' => $n];
        $type = SurveyQuestionType::tryFrom(is_string($q['type'] ?? null) ? $q['type'] : '');
        if ($type === null) {
            throw SurveyRuleException::key('question_type_invalid', $at);
        }
        $text = $this->text($q['text'] ?? '', self::MAX_QUESTION_TEXT, 'question_text', $at);
        if ($text === '') {
            throw SurveyRuleException::key('question_text_required', $at);
        }
        $questionId = $this->id($q['id'] ?? null, 'q', $at, $seen, $known === null ? null : array_fill_keys(array_keys($known['questions']), true));
        $knownOptions = $known === null ? null : ($questionId !== null ? ($known['questions'][$questionId] ?? []) : []);
        $options = [];
        $config = [];
        if ($type->hasOptions()) {
            foreach ($this->listOfArrays($q['options'] ?? []) as $o) {
                $label = $this->text($o['text'] ?? '', self::MAX_OPTION_TEXT, 'option_text', $at);
                if ($label === '') {
                    continue; // dòng lựa chọn trống bị bỏ qua
                }
                $options[] = ['id' => $this->id($o['id'] ?? null, 'o', $at, $seen, $knownOptions), 'text' => $label, 'enabled' => (bool) ($o['enabled'] ?? true)];
            }
            if (count($options) < 2) {
                throw SurveyRuleException::key('options_min', $at);
            }
            if (count($options) > self::MAX_OPTIONS) {
                throw SurveyRuleException::key('options_max', $at + ['max' => self::MAX_OPTIONS]);
            }
        } elseif ($type === SurveyQuestionType::Scale) {
            $cfg = is_array($q['config'] ?? null) ? $q['config'] : [];
            $min = (int) ($cfg['min'] ?? 1);
            $max = (int) ($cfg['max'] ?? 5);
            if ($min < 0 || $max > 10 || $max <= $min) {
                throw SurveyRuleException::key('scale_range', $at);
            }
            $config = [
                'min' => $min,
                'max' => $max,
                'min_label' => $this->text($cfg['min_label'] ?? '', self::MAX_SCALE_LABEL, 'scale_label', $at),
                'max_label' => $this->text($cfg['max_label'] ?? '', self::MAX_SCALE_LABEL, 'scale_label', $at),
            ];
        }

        return [
            'id' => $questionId,
            'type' => $type->value,
            'text' => $text,
            'description' => $this->nullIfBlank($this->text($q['description'] ?? '', self::MAX_QUESTION_DESC, 'question_description', $at)),
            'required' => (bool) ($q['required'] ?? false),
            'enabled' => (bool) ($q['enabled'] ?? true),
            'config' => $config,
            'options' => $options,
        ];
    }

    /** Chuỗi hợp lệ: scalar, đã trim, KHÔNG vượt giới hạn (vượt thì báo lỗi, không cắt âm thầm). */
    private function text(mixed $value, int $max, string $field, array $at): string
    {
        if ($value !== null && ! is_scalar($value)) {
            throw SurveyRuleException::key('field_invalid', $at + ['field' => __('admin.survey.error_field.'.$field)]);
        }
        $value = trim((string) $value);
        if (mb_strlen($value) > $max) {
            throw SurveyRuleException::key('too_long', $at + ['field' => __('admin.survey.error_field.'.$field), 'max' => $max]);
        }

        return $value;
    }

    /**
     * ID phải là số nguyên dương, không lặp trong payload và (khi biết) thuộc đúng khảo sát.
     *
     * @param  array{g: array<int, true>, q: array<int, true>, o: array<int, true>}  $seen
     * @param  ?array<int, mixed>  $knownSet  khóa = ID hợp lệ
     */
    private function id(mixed $value, string $kind, array $at, array &$seen, ?array $knownSet): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! (is_int($value) || (is_string($value) && ctype_digit($value))) || (int) $value <= 0) {
            throw SurveyRuleException::key('id_invalid', $at);
        }
        $id = (int) $value;
        if (isset($seen[$kind][$id])) {
            throw SurveyRuleException::key('id_duplicate', $at);
        }
        $seen[$kind][$id] = true;
        if ($knownSet !== null && ! array_key_exists($id, $knownSet)) {
            throw SurveyRuleException::key('id_foreign', $at);
        }

        return $id;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Cấu trúc hiện tại dưới dạng mảng cho trình dựng (ID thật; không có chỉ số 1..N làm định danh).
     *
     * @return array<int, array<string, mixed>>
     */
    public function structureForBuilder(KhaoSat $survey): array
    {
        $this->data->withStructure($survey);

        return $survey->nhom->map(fn ($g) => [
            'id' => $g->nhom_id,
            'name' => $g->nhom_ten,
            'description' => $g->nhom_mo_ta,
            'questions' => $g->cauHoi->map(fn ($q) => [
                'id' => $q->ch_id,
                'type' => $q->kieu()->value,
                'text' => $q->ch_noidung,
                'description' => $q->ch_mo_ta,
                'required' => (bool) $q->ch_bat_buoc,
                'enabled' => (bool) $q->ch_trang_thai,
                'config' => $q->ch_cau_hinh ?? new \stdClass,
                'options' => $q->traLoi->map(fn ($o) => ['id' => $o->trl_id, 'text' => $o->trl_noidung, 'enabled' => (bool) $o->trl_trang_thai])->values()->all(),
            ])->values()->all(),
        ])->values()->all();
    }

    // ------------------------------------------------------------------ trạng thái

    public function transition(KhaoSat $survey, SurveyStatus $to, ?int $userId): KhaoSat
    {
        $from = $survey->status();
        if (! $from->canTransitionTo($to)) {
            throw SurveyRuleException::key('transition_invalid', ['from' => $from->label(), 'to' => $to->label()]);
        }
        if ($to === SurveyStatus::Open) {
            $this->assertPublishable($survey);
        }

        $updated = $this->data->update($survey, [
            'ks_status' => $to->value,
            'ks_trang_thai' => $to === SurveyStatus::Open, // cờ tương thích với nền cũ
            'ks_updated_by' => $userId,
        ]);
        SurveyAudit::record('status', $survey->ks_id, null, ['from' => $from->value, 'to' => $to->value]);

        return $updated;
    }

    private function assertPublishable(KhaoSat $survey): void
    {
        // Phải có ít nhất một câu hỏi THỰC SỰ hiển thị được trên trang công khai (cùng schema với SurveyFormService).
        if ($this->forms->schema($survey)['question_count'] < 1) {
            throw SurveyRuleException::key('publish_needs_question');
        }
        // Câu hỏi lựa chọn đang bật phải có ít nhất 2 lựa chọn đang bật.
        $this->data->withStructure($survey);
        $questions = $survey->nhom->flatMap->cauHoi->concat($survey->cauHoi);
        foreach ($questions as $q) {
            if ($q->ch_trang_thai && $q->kieu()->hasOptions() && $q->traLoi->where('trl_trang_thai', true)->count() < 2) {
                throw SurveyRuleException::key('publish_choice_options', ['question' => Str::limit($q->ch_noidung, 60)]);
            }
        }
        if ($survey->ks_ket_thuc !== null && $survey->ks_ket_thuc->isPast()) {
            throw SurveyRuleException::key('publish_end_in_past');
        }
        if (empty($survey->ks_ma)) {
            throw SurveyRuleException::key('publish_needs_slug');
        }
    }

    // ------------------------------------------------------------------ nhân bản / xóa an toàn

    public function duplicate(KhaoSat $survey, ?int $userId): KhaoSat
    {
        return $this->data->transaction(function () use ($survey, $userId) {
            $copy = $this->data->create([
                'ks_ten' => Str::limit($survey->ks_ten, 470, '').' '.__('admin.survey.copy_suffix'),
                'ks_ma' => $this->uniqueSlug(($survey->ks_ma ?: $survey->ks_ten).'-copy'),
                'ks_mo_ta_ngan' => $survey->ks_mo_ta_ngan,
                'ks_gioi_thieu' => $survey->ks_gioi_thieu,
                'ks_loai' => $survey->ks_loai,
                'ks_status' => SurveyStatus::Draft->value,
                'ks_trang_thai' => false,
                'ks_bat_dau' => null,
                'ks_ket_thuc' => null,
                'ks_cho_phep_an_danh' => $survey->ks_cho_phep_an_danh,
                'ks_yeu_cau_thong_tin' => $survey->ks_yeu_cau_thong_tin,
                'ks_so_lan_gui' => $survey->ks_so_lan_gui,
                'ks_noi_dung_hoan_thanh' => $survey->ks_noi_dung_hoan_thanh,
                'ks_ghichu' => $survey->ks_ghichu,
                'ks_created_by' => $userId,
                'ks_updated_by' => $userId,
            ]);
            $this->data->copyStructure($survey, $copy);
            SurveyAudit::record('duplicate', $survey->ks_id, null, ['copy_id' => $copy->ks_id]);

            return $copy;
        });
    }

    /** Xóa an toàn = xóa mềm; không bao giờ xóa vật lý khảo sát hay lượt tham gia. Khảo sát đang mở phải kết thúc/tạm dừng trước. */
    public function delete(KhaoSat $survey): void
    {
        if ($survey->status() === SurveyStatus::Open) {
            throw SurveyRuleException::key('delete_open');
        }
        $this->data->softDelete($survey);
        SurveyAudit::record('delete', $survey->ks_id, null, ['status' => $survey->status()->value]);
    }

    public function restore(KhaoSat $survey): void
    {
        $this->data->restore($survey);
        SurveyAudit::record('restore', $survey->ks_id);
    }

    /** Có thể sửa gì ở trạng thái hiện tại (cho giao diện và kiểm tra). */
    public function capabilities(KhaoSat $survey): array
    {
        $status = $survey->status();

        return [
            'edit_info' => $status->allowsInfoEdit(),
            'edit_slug' => $status === SurveyStatus::Draft && ! $this->collectionLocked($survey),
            'collection_locked' => $this->collectionLocked($survey),
            'edit_structure' => $status->allowsStructureEdit(),
            'transitions' => $status->next(),
            'has_responses' => $this->data->participationCount($survey->ks_id) > 0,
        ];
    }
}
