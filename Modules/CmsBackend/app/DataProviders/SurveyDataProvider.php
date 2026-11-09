<?php

namespace Modules\CmsBackend\DataProviders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CmsFrontend\Models\CauHoi;
use Modules\CmsFrontend\Models\KetQua;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatNhom;
use Modules\CmsFrontend\Models\KhaoSatUser;
use Modules\CmsFrontend\Models\TraLoi;

/**
 * Truy vấn và ghi dữ liệu cấu trúc khảo sát (khảo sát, nhóm, câu hỏi, lựa chọn).
 * Không có điều kiện theme/project: dữ liệu khảo sát không phụ thuộc theme.
 */
class SurveyDataProvider
{
    // ------------------------------------------------------------------ khảo sát

    /**
     * @param  array{q?: ?string, status?: ?string}  $filters  status: draft|open|paused|ended|trashed
     */
    public function paginateSurveys(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = KhaoSat::query();
        $status = $filters['status'] ?? null;
        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status) {
            $query->where('ks_status', $status);
        }
        if (! empty($filters['q'])) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower((string) $filters['q'])).'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(ks_ten) LIKE ?', [$like])->orWhereRaw('LOWER(ks_ma) LIKE ?', [$like]));
        }
        // Cụm Định hướng nghề nghiệp chỉ có khảo sát loại career; cụm Khảo sát có mọi loại còn lại.
        if (($filters['cluster'] ?? null) === 'career') {
            $query->where('ks_loai', 'career');
        } elseif (($filters['cluster'] ?? null) === 'survey') {
            $query->where(fn ($q) => $q->whereNull('ks_loai')->orWhere('ks_loai', '!=', 'career'));
        }

        return $query
            ->withCount([
                'cauHoi as so_cau_hoi' => fn ($q) => $q->where('ch_trang_thai', true),
                'luotThamGia as so_luot',
                'luotThamGia as so_hoan_thanh' => fn ($q) => $q->where('ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED),
            ])
            ->orderByDesc('ks_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Khảo sát đang nhận phản hồi cho trang danh sách công khai: chưa xóa, trạng thái Mở, đã tới giờ bắt đầu (hoặc không giới hạn)
     * và chưa tới giờ kết thúc (hoặc không giới hạn). Cùng điều kiện với SurveyPublicService::availability() = OPEN.
     *
     * @return \Illuminate\Support\Collection<int, KhaoSat>
     */
    public function openForPublic(\Carbon\CarbonInterface $now): \Illuminate\Support\Collection
    {
        return KhaoSat::query()
            ->where('ks_status', 'open')
            ->whereNotNull('ks_ma')
            ->where(fn ($q) => $q->whereNull('ks_bat_dau')->orWhere('ks_bat_dau', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ks_ket_thuc')->orWhere('ks_ket_thuc', '>', $now))
            ->orderByDesc('ks_id')
            ->get(['ks_id', 'ks_ten', 'ks_ma', 'ks_loai', 'ks_mo_ta_ngan', 'ks_ket_thuc']);
    }

    public function find(int $id, bool $withTrashed = false): ?KhaoSat
    {
        $query = KhaoSat::query();
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function findBySlug(string $slug): ?KhaoSat
    {
        return KhaoSat::query()->where('ks_ma', $slug)->first();
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return KhaoSat::withTrashed()->where('ks_ma', $slug)->when($exceptId, fn ($q) => $q->where('ks_id', '!=', $exceptId))->exists();
    }

    /** Khảo sát kèm nhóm → câu hỏi → lựa chọn (chỉ bản ghi chưa xóa). */
    public function withStructure(KhaoSat $survey): KhaoSat
    {
        return $survey->load([
            'nhom.cauHoi.traLoi',
            'cauHoi' => fn ($q) => $q->whereNull('nhom_id')->with('traLoi'),
        ]);
    }

    public function create(array $attributes): KhaoSat
    {
        return KhaoSat::create($attributes);
    }

    public function update(KhaoSat $survey, array $attributes): KhaoSat
    {
        $survey->fill($attributes)->save();

        return $survey;
    }

    public function softDelete(KhaoSat $survey): void
    {
        $survey->delete();
    }

    public function restore(KhaoSat $survey): void
    {
        $survey->restore();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    // ------------------------------------------------------------------ đếm / kiểm tra

    public function participationCount(int $surveyId): int
    {
        return KhaoSatUser::query()->where('ks_id', $surveyId)->count();
    }

    public function activeQuestionCount(int $surveyId): int
    {
        return CauHoi::query()->where('ks_id', $surveyId)->where('ch_trang_thai', true)->count();
    }

    public function questionHasAnswers(int $questionId): bool
    {
        return KetQua::query()->where('ch_id', $questionId)->exists();
    }

    public function optionHasAnswers(int $optionId): bool
    {
        return KetQua::query()->where('trl_id', $optionId)->exists();
    }

    /**
     * Mọi ID cấu trúc thuộc khảo sát (kể cả đã xóa mềm) để kiểm tra payload không trỏ sang khảo sát khác.
     *
     * @return array{groups: array<int, true>, questions: array<int, array<int, true>>}
     */
    public function structureIds(int $surveyId): array
    {
        $groups = [];
        foreach (KhaoSatNhom::withTrashed()->where('ks_id', $surveyId)->pluck('nhom_id') as $id) {
            $groups[(int) $id] = true;
        }
        $questions = [];
        foreach (CauHoi::withTrashed()->where('ks_id', $surveyId)->pluck('ch_id') as $id) {
            $questions[(int) $id] = [];
        }
        if ($questions !== []) {
            foreach (TraLoi::withTrashed()->whereIn('ch_id', array_keys($questions))->get(['trl_id', 'ch_id']) as $o) {
                $questions[(int) $o->ch_id][(int) $o->trl_id] = true;
            }
        }

        return ['groups' => $groups, 'questions' => $questions];
    }

    // ------------------------------------------------------------------ ghi cấu trúc

    /**
     * Ghi cấu trúc đã được Service kiểm tra. Định danh thật: phần tử có `id` thuộc khảo sát này thì cập nhật, ngược lại tạo mới
     * với mã UUID. Phần tử không còn trong dữ liệu: có câu trả lời → xóa mềm (không mất dữ liệu), chưa có → xóa hẳn.
     *
     * @param  array<int, array<string, mixed>>  $groups  [{id?, name, description, questions: [{id?, type, text, description, required, enabled, config, options: [{id?, text, enabled}]}]}]
     */
    public function persistStructure(KhaoSat $survey, array $groups): void
    {
        $existingGroups = KhaoSatNhom::query()->where('ks_id', $survey->ks_id)->get()->keyBy('nhom_id');
        $existingQuestions = CauHoi::withTrashed()->where('ks_id', $survey->ks_id)->get()->keyBy('ch_id');
        $keptGroups = [];
        $keptQuestions = [];
        $questionOrder = 0;

        foreach (array_values($groups) as $gIndex => $group) {
            $model = $existingGroups->get((int) ($group['id'] ?? 0)) ?? new KhaoSatNhom(['ks_id' => $survey->ks_id, 'nhom_ma' => (string) Str::uuid()]);
            $model->fill([
                'nhom_ten' => $group['name'],
                'nhom_mo_ta' => $group['description'] ?? null,
                'nhom_thu_tu' => $gIndex,
            ])->save();
            $keptGroups[] = $model->nhom_id;

            foreach (array_values($group['questions'] ?? []) as $question) {
                $q = $existingQuestions->get((int) ($question['id'] ?? 0));
                if ($q !== null && $q->trashed()) {
                    $q = null; // câu hỏi đã ngưng không được "sống lại" bằng id cũ: tạo bản mới
                }
                $q ??= new CauHoi(['ks_id' => $survey->ks_id, 'ch_ma' => (string) Str::uuid()]);
                $q->fill([
                    'nhom_id' => $model->nhom_id,
                    'ch_type' => $question['type'],
                    'ch_noidung' => $question['text'],
                    'ch_mo_ta' => $question['description'] ?? null,
                    'ch_bat_buoc' => (bool) ($question['required'] ?? false),
                    'ch_trang_thai' => (bool) ($question['enabled'] ?? true),
                    'ch_cau_hinh' => $question['config'] ?: null,
                    'ch_thu_tu' => $questionOrder++,
                ])->save();
                $keptQuestions[] = $q->ch_id;
                $this->persistOptions($q, $question['options'] ?? []);
            }
        }

        // Câu hỏi bị gỡ khỏi cấu trúc
        foreach ($existingQuestions as $id => $q) {
            if (in_array($id, $keptQuestions, true) || $q->trashed()) {
                continue;
            }
            $this->retireQuestion($q);
        }
        // Nhóm bị gỡ
        foreach ($existingGroups as $id => $g) {
            if (in_array($id, $keptGroups, true)) {
                continue;
            }
            $referenced = CauHoi::withTrashed()->where('nhom_id', $id)->exists();
            $referenced ? $g->delete() : $g->forceDelete();
        }
    }

    /** @param array<int, array<string, mixed>> $options */
    private function persistOptions(CauHoi $question, array $options): void
    {
        $existing = TraLoi::withTrashed()->where('ch_id', $question->ch_id)->get()->keyBy('trl_id');
        $kept = [];
        foreach (array_values($options) as $index => $option) {
            $o = $existing->get((int) ($option['id'] ?? 0));
            if ($o !== null && $o->trashed()) {
                $o = null;
            }
            if ($o === null) {
                $code = (string) Str::uuid();
                $o = new TraLoi(['ch_id' => $question->ch_id, 'trl_ma' => $code, 'trl_value' => $code]);
            }
            $o->fill([
                'trl_noidung' => $option['text'],
                'trl_thu_tu' => $index,
                'trl_trang_thai' => (bool) ($option['enabled'] ?? true),
            ])->save();
            $kept[] = $o->trl_id;
        }
        foreach ($existing as $id => $o) {
            if (in_array($id, $kept, true) || $o->trashed()) {
                continue;
            }
            $this->optionHasAnswers($id) ? $o->delete() : $o->forceDelete();
        }
    }

    private function retireQuestion(CauHoi $q): void
    {
        if ($this->questionHasAnswers($q->ch_id)) {
            $q->delete(); // giữ lại câu hỏi và lựa chọn để kết quả cũ vẫn đọc được

            return;
        }
        TraLoi::withTrashed()->where('ch_id', $q->ch_id)->forceDelete();
        $q->forceDelete();
    }

    /** Sao chép cấu trúc (không sao chép lượt tham gia/câu trả lời) sang khảo sát mới. */
    public function copyStructure(KhaoSat $from, KhaoSat $to): void
    {
        $groups = [];
        $this->withStructure($from);
        foreach ($from->nhom as $nhom) {
            $groups[] = [
                'name' => $nhom->nhom_ten,
                'description' => $nhom->nhom_mo_ta,
                'questions' => $nhom->cauHoi->map(fn (CauHoi $q) => [
                    'type' => $q->kieu()->value,
                    'text' => $q->ch_noidung,
                    'description' => $q->ch_mo_ta,
                    'required' => $q->ch_bat_buoc,
                    'enabled' => $q->ch_trang_thai,
                    'config' => $q->ch_cau_hinh,
                    'options' => $q->traLoi->map(fn (TraLoi $o) => ['text' => $o->trl_noidung, 'enabled' => $o->trl_trang_thai])->all(),
                ])->all(),
            ];
        }
        $this->persistStructure($to, $groups);
    }
}
