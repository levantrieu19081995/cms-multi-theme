<?php

namespace Modules\CmsBackend\DataProviders;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CmsFrontend\Models\CauHoi;
use Modules\CmsFrontend\Models\KetQua;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;

/**
 * Lượt tham gia và câu trả lời: ghi từ trang công khai, đọc cho quản trị (danh sách, chi tiết, thống kê, xuất).
 * Không có điều kiện theme/project.
 */
class SurveyResponseDataProvider
{
    // ------------------------------------------------------------------ ghi (trang công khai)

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function findParticipationByCode(int $surveyId, string $code, bool $lock = false): ?KhaoSatUser
    {
        $query = KhaoSatUser::query()->where('ks_id', $surveyId)->where('ksu_ma', $code);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /** Lượt đang làm dở gần nhất của cùng trình duyệt (dùng lại để không làm phồng số "bắt đầu"). */
    public function findResumable(int $surveyId, string $fingerprint, CarbonInterface $since): ?KhaoSatUser
    {
        return KhaoSatUser::query()
            ->where('ks_id', $surveyId)
            ->where('ksu_dau_vet', $fingerprint)
            ->where('ksu_trang_thai', KhaoSatUser::STATUS_IN_PROGRESS)
            ->where('ksu_created_at', '>=', $since)
            ->orderByDesc('ksu_id')
            ->first();
    }

    public function completedCountByFingerprint(int $surveyId, string $fingerprint): int
    {
        return KhaoSatUser::query()
            ->where('ks_id', $surveyId)
            ->where('ksu_dau_vet', $fingerprint)
            ->where('ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED)
            ->count();
    }

    public function createParticipation(array $attributes): KhaoSatUser
    {
        return KhaoSatUser::create($attributes);
    }

    public function updateParticipation(KhaoSatUser $participation, array $attributes): KhaoSatUser
    {
        $participation->fill($attributes)->save();

        return $participation;
    }

    /** @param array<int, array<string, mixed>> $rows [{ch_id, trl_id, kskq_trl_value, kskq_van_ban}] */
    public function insertAnswers(KhaoSatUser $participation, array $rows): void
    {
        $now = now();
        foreach (array_chunk($rows, 200) as $chunk) {
            KetQua::insert(array_map(fn (array $row) => [
                'ks_id' => $participation->ks_id,
                'ksu_id' => $participation->ksu_id,
                'ch_id' => $row['ch_id'],
                'trl_id' => $row['trl_id'] ?? null,
                'kskq_trl_value' => (string) ($row['kskq_trl_value'] ?? ''),
                'kskq_van_ban' => $row['kskq_van_ban'] ?? null,
                'kskq_created_at' => $now,
            ], $chunk));
        }
    }

    /**
     * Khóa dòng khảo sát (SELECT … FOR UPDATE) cho tới hết transaction hiện tại và trả về bản mới nhất. Phải gọi trong transaction.
     * Đây là "tài nguyên chung" tuần tự hóa mọi lượt bắt đầu/gửi của một khảo sát.
     */
    public function lockSurvey(int $surveyId): ?KhaoSat
    {
        return KhaoSat::withTrashed()->whereKey($surveyId)->lockForUpdate()->first();
    }

    public function find(int $surveyId): ?KhaoSat
    {
        return KhaoSat::query()->find($surveyId);
    }

    // ------------------------------------------------------------------ đọc (quản trị)

    /**
     * Lượt tham gia của một khảo sát theo bộ lọc. Bộ lọc dùng chung cho danh sách, thống kê và xuất.
     *
     * @param  array{q?: ?string, status?: ?string, from?: ?string, to?: ?string, anonymous?: ?string}  $f
     */
    public function participationsQuery(int $surveyId, array $f): Builder
    {
        $query = KhaoSatUser::query()->where('ks_id', $surveyId);
        if (in_array($f['status'] ?? null, [KhaoSatUser::STATUS_COMPLETED, KhaoSatUser::STATUS_IN_PROGRESS], true)) {
            $query->where('ksu_trang_thai', $f['status']);
        }
        if (! empty($f['q'])) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower((string) $f['q'])).'%';
            $query->where(fn ($q) => $q
                ->whereRaw('LOWER(ksu_ho_ten) LIKE ?', [$like])
                ->orWhereRaw('LOWER(ksu_so_dien_thoai) LIKE ?', [$like])
                ->orWhereRaw('LOWER(ksu_email) LIKE ?', [$like])
                ->orWhereRaw('LOWER(ksu_ma) LIKE ?', [$like]));
        }
        if (! empty($f['from'])) {
            $query->whereDate('ksu_created_at', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $query->whereDate('ksu_created_at', '<=', $f['to']);
        }
        if (($f['anonymous'] ?? null) === '1') {
            $query->where('ksu_an_danh', true);
        } elseif (($f['anonymous'] ?? null) === '0') {
            $query->where('ksu_an_danh', false);
        }

        return $query;
    }

    public function paginateParticipations(int $surveyId, array $f, int $perPage = 20): LengthAwarePaginator
    {
        return $this->participationsQuery($surveyId, $f)->orderByDesc('ksu_id')->paginate($perPage)->withQueryString();
    }

    /** Câu trả lời của một lượt, kèm câu hỏi (kể cả đã ngưng) và lựa chọn, theo thứ tự câu hỏi. */
    public function answersOf(KhaoSatUser $participation): Collection
    {
        return KetQua::query()
            ->where('ksu_id', $participation->ksu_id)
            ->with(['cauHoi.nhom' => fn ($q) => $q->withTrashed(), 'luaChon'])
            ->get()
            ->sortBy(fn (KetQua $k) => sprintf('%010d-%010d', $k->cauHoi?->ch_thu_tu ?? 9999999999, $k->kskq_id))
            ->values();
    }

    /** Câu hỏi để báo cáo: đang dùng + đã ngưng nhưng có câu trả lời. */
    public function questionsForReport(int $surveyId): Collection
    {
        $answered = KetQua::query()->where('ks_id', $surveyId)->distinct()->pluck('ch_id');

        return CauHoi::withTrashed()
            ->where('ks_id', $surveyId)
            ->where(fn ($q) => $q->whereNull('ch_deleted_at')->orWhereIn('ch_id', $answered))
            ->with(['nhom' => fn ($q) => $q->withTrashed(), 'traLoi' => fn ($q) => $q->withTrashed()])
            ->orderBy('ch_thu_tu')->orderBy('ch_id')
            ->get();
    }

    /** @return array{started: int, completed: int, avg_seconds: ?float} */
    public function overview(int $surveyId, array $f): array
    {
        $base = $this->participationsQuery($surveyId, ['from' => $f['from'] ?? null, 'to' => $f['to'] ?? null]);
        $started = (clone $base)->count();
        $completed = (clone $base)->where('ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED)->count();
        $avg = (clone $base)->where('ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED)->whereNotNull('ksu_thoi_luong')->avg('ksu_thoi_luong');

        return ['started' => $started, 'completed' => $completed, 'avg_seconds' => $avg === null ? null : (float) $avg];
    }

    /**
     * Số lượt hoàn thành có kết quả định hướng nghề nghiệp, gom theo mã Holland và trường (school_key/school_name).
     *
     * @return array<int, array{code: string, school_key: ?string, school_name: ?string, n: int}>
     */
    public function careerBreakdown(int $surveyId, array $f): array
    {
        return $this->participationsQuery($surveyId, ['from' => $f['from'] ?? null, 'to' => $f['to'] ?? null])
            ->where('ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED)
            ->whereNotNull('ksu_ket_qua')->where('ksu_ket_qua', '!=', '')
            ->reorder()->selectRaw('ksu_ket_qua as code, school_key, school_name, COUNT(*) as n')
            ->groupBy('ksu_ket_qua', 'school_key', 'school_name')->get()
            ->map(fn ($r) => ['code' => (string) $r->code, 'school_key' => $r->school_key, 'school_name' => $r->school_name, 'n' => (int) $r->n])->all();
    }

    /** @return array<int, array{day: string, started: int, completed: int}> */
    public function daily(int $surveyId, array $f): array
    {
        return $this->participationsQuery($surveyId, ['from' => $f['from'] ?? null, 'to' => $f['to'] ?? null])
            ->selectRaw("DATE(ksu_created_at) as day, COUNT(*) as started, SUM(CASE WHEN ksu_trang_thai = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupByRaw('DATE(ksu_created_at)')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => (string) $r->day, 'started' => (int) $r->started, 'completed' => (int) $r->completed])
            ->all();
    }

    /**
     * Số lần chọn từng lựa chọn / từng giá trị, số người trả lời từng câu — chỉ tính lượt đã hoàn thành, chưa xóa.
     *
     * @return array{options: array<int, array<int, int>>, values: array<int, array<string, int>>, respondents: array<int, int>, texts: array<int, int>}
     */
    public function answerCounts(int $surveyId, array $f): array
    {
        $base = fn () => DB::table('ks_ket_qua as kq')
            ->join('ks_khao_sat_users as u', 'u.ksu_id', '=', 'kq.ksu_id')
            ->where('u.ks_id', $surveyId)
            ->whereNull('u.ksu_deleted_at')
            ->where('u.ksu_trang_thai', KhaoSatUser::STATUS_COMPLETED)
            ->when(! empty($f['from']), fn ($q) => $q->whereDate('u.ksu_created_at', '>=', $f['from']))
            ->when(! empty($f['to']), fn ($q) => $q->whereDate('u.ksu_created_at', '<=', $f['to']));

        $options = [];
        foreach ($base()->whereNotNull('kq.trl_id')->groupBy('kq.ch_id', 'kq.trl_id')->selectRaw('kq.ch_id, kq.trl_id, COUNT(*) as n')->get() as $r) {
            $options[(int) $r->ch_id][(int) $r->trl_id] = (int) $r->n;
        }
        $values = [];
        foreach ($base()->whereNull('kq.trl_id')->whereNull('kq.kskq_van_ban')->groupBy('kq.ch_id', 'kq.kskq_trl_value')->selectRaw('kq.ch_id, kq.kskq_trl_value as v, COUNT(*) as n')->get() as $r) {
            $values[(int) $r->ch_id][(string) $r->v] = (int) $r->n;
        }
        $texts = [];
        foreach ($base()->whereNotNull('kq.kskq_van_ban')->groupBy('kq.ch_id')->selectRaw('kq.ch_id, COUNT(*) as n')->get() as $r) {
            $texts[(int) $r->ch_id] = (int) $r->n;
        }
        $respondents = [];
        foreach ($base()->groupBy('kq.ch_id')->selectRaw('kq.ch_id, COUNT(DISTINCT kq.ksu_id) as n')->get() as $r) {
            $respondents[(int) $r->ch_id] = (int) $r->n;
        }

        return compact('options', 'values', 'respondents', 'texts');
    }

    /**
     * Câu trả lời của một nhóm lượt (cho xuất chi tiết), gom theo lượt.
     *
     * @param  int[]  $participationIds
     * @return Collection<int, Collection<int, KetQua>>
     */
    public function answersByParticipation(array $participationIds): Collection
    {
        return KetQua::query()->whereIn('ksu_id', $participationIds)->with('luaChon')->get()->groupBy('ksu_id');
    }

    public function softDeleteParticipation(KhaoSatUser $participation): void
    {
        $participation->delete();
    }
}
