<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Option;
use Illuminate\Support\Collection;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Support\Riasec;
use Modules\CmsFrontend\Models\CareerGroup;
use Modules\CmsFrontend\Models\CareerMajor;
use Modules\CmsFrontend\Models\KhaoSatUser;

/**
 * Định hướng nghề nghiệp: nhóm RIASEC (tên/mô tả chỉnh được), ngành gợi ý do trường khai báo và kết quả của một lượt.
 * Không có dữ liệu viết cứng ngoài định nghĩa chuẩn của 6 nhóm (lang/career.php) dùng khi quản trị chưa chỉnh.
 */
class CareerService
{
    public const MAX_NAME = 255;

    public const MAX_DESCRIPTION = 2000;

    public const MAX_MAJORS = 500;

    /**
     * Sáu nhóm theo thứ tự R, I, A, S, E, C. Tên/mô tả quản trị đã chỉnh được ưu tiên; thiếu thì lấy chữ mặc định trong lang.
     *
     * @return array<string, array{code: string, name: string, description: string, custom_name: ?string, custom_description: ?string}>
     */
    public function groups(): array
    {
        $rows = CareerGroup::query()->get()->keyBy('cg_ma');
        $out = [];
        foreach (Riasec::LETTERS as $letter) {
            $row = $rows->get($letter);
            $customName = $row !== null && trim((string) $row->cg_ten) !== '' ? (string) $row->cg_ten : null;
            $customDescription = $row !== null && trim((string) $row->cg_mo_ta) !== '' ? (string) $row->cg_mo_ta : null;
            $out[$letter] = [
                'code' => $letter,
                'name' => $customName ?? __('career.groups.'.$letter.'.name'),
                'description' => $customDescription ?? __('career.groups.'.$letter.'.description'),
                'custom_name' => $customName,
                'custom_description' => $customDescription,
            ];
        }

        return $out;
    }

    /**
     * Lưu tên/mô tả nhóm. Để trống cả hai = quay về chữ mặc định.
     *
     * @param  array<string, array{name?: ?string, description?: ?string}>  $input
     */
    public function saveGroups(array $input): void
    {
        foreach (Riasec::LETTERS as $letter) {
            $name = trim((string) ($input[$letter]['name'] ?? ''));
            $description = trim((string) ($input[$letter]['description'] ?? ''));
            if (mb_strlen($name) > self::MAX_NAME || mb_strlen($description) > self::MAX_DESCRIPTION) {
                throw SurveyRuleException::key('career_group_too_long', ['group' => $letter]);
            }
            if ($name === '' && $description === '') {
                CareerGroup::query()->where('cg_ma', $letter)->delete();

                continue;
            }
            CareerGroup::query()->updateOrCreate(['cg_ma' => $letter], ['cg_ten' => $name ?: null, 'cg_mo_ta' => $description ?: null]);
        }
    }

    /**
     * Danh sách trường (THPT…) mà người làm có thể chọn, lấy từ tùy chọn `school_list` của hệ thống (JSON [{key, name}]).
     * Rỗng thì trang làm khảo sát không hỏi trường. Chỉ nhận phần tử hợp lệ (khóa ngắn, tên không rỗng).
     *
     * @return array<int, array{key: string, name: string}>
     */
    public function schools(): array
    {
        $raw = Option::query()->where('key', 'school_list')->value('value');
        $items = json_decode((string) $raw, true);
        $out = [];
        foreach (is_array($items) ? $items : [] as $item) {
            $key = is_array($item) && is_string($item['key'] ?? null) ? trim($item['key']) : '';
            $name = is_array($item) && is_string($item['name'] ?? null) ? trim($item['name']) : '';
            if ($key !== '' && mb_strlen($key) <= 100 && $name !== '' && mb_strlen($name) <= 255 && ! isset($out[$key])) {
                $out[$key] = ['key' => $key, 'name' => $name];
            }
        }

        return array_values($out);
    }

    /** Trường theo khóa trong danh sách; khóa không có trong danh sách thì bỏ qua (null). @return ?array{key: string, name: string} */
    public function school(?string $key): ?array
    {
        foreach ($this->schools() as $school) {
            if ($school['key'] === $key) {
                return $school;
            }
        }

        return null;
    }

    /** @return Collection<int, CareerMajor> */
    public function majors(): Collection
    {
        return CareerMajor::query()->orderBy('cm_thu_tu')->orderBy('cm_id')->get();
    }

    /** @param array{name: string, code: string, description?: ?string, url?: ?string, order?: int|string|null, active?: bool} $data */
    public function saveMajor(?CareerMajor $major, array $data, ?int $userId): CareerMajor
    {
        $code = Riasec::normalizeCode($data['code'] ?? '');
        if ($code === '') {
            throw SurveyRuleException::key('career_major_code');
        }
        if ($major === null && CareerMajor::query()->count() >= self::MAX_MAJORS) {
            throw SurveyRuleException::key('career_major_limit', ['max' => self::MAX_MAJORS]);
        }
        $attributes = [
            'cm_ten' => trim($data['name']),
            'cm_ma_riasec' => $code,
            'cm_mo_ta' => ($d = trim((string) ($data['description'] ?? ''))) === '' ? null : $d,
            'cm_url' => ($u = trim((string) ($data['url'] ?? ''))) === '' ? null : $u,
            'cm_thu_tu' => max(0, (int) ($data['order'] ?? 0)),
            'cm_trang_thai' => (bool) ($data['active'] ?? true),
        ];
        if ($major === null) {
            return CareerMajor::query()->create($attributes + ['cm_created_by' => $userId]);
        }
        $major->fill($attributes)->save();

        return $major;
    }

    public function deleteMajor(CareerMajor $major): void
    {
        $major->delete();
    }

    /**
     * Ngành gợi ý cho một mã Holland: chỉ ngành đang bật có độ phù hợp > 0, cao trước rồi theo thứ tự quản trị nhập.
     *
     * @return Collection<int, CareerMajor>
     */
    public function suggestions(?string $code, int $limit = 6): Collection
    {
        $code = Riasec::normalizeCode($code);
        if ($code === '') {
            return collect();
        }

        return CareerMajor::query()->where('cm_trang_thai', true)->orderBy('cm_thu_tu')->orderBy('cm_id')->get()
            ->map(fn (CareerMajor $m) => ['major' => $m, 'affinity' => Riasec::affinity($code, $m->cm_ma_riasec)])
            ->filter(fn (array $row) => $row['affinity'] > 0)
            ->sortByDesc('affinity')->take($limit)->pluck('major')->values();
    }

    /**
     * Kết quả định hướng của một lượt đã hoàn thành (cho trang cảm ơn và màn quản trị). Null nếu lượt không có kết quả RIASEC.
     *
     * @return ?array{code: string, scores: array<int, array{code: string, name: string, description: string, pct: ?int, rank: ?int}>, top: array<int, array<string, mixed>>, majors: Collection<int, CareerMajor>}
     */
    public function resultFor(KhaoSatUser $participation, int $majorLimit = 6): ?array
    {
        $code = Riasec::normalizeCode($participation->ksu_ket_qua);
        $stored = json_decode((string) $participation->ksu_diem, true);
        if ($code === '' || ! is_array($stored)) {
            return null;
        }
        $groups = $this->groups();
        $scores = [];
        foreach (Riasec::LETTERS as $letter) {
            $pct = isset($stored[$letter]['pct']) ? (int) $stored[$letter]['pct'] : null;
            $position = strpos($code, $letter);
            $scores[] = [
                'code' => $letter,
                'name' => $groups[$letter]['name'],
                'description' => $groups[$letter]['description'],
                'pct' => $pct,
                'rank' => $position === false ? null : $position + 1,
            ];
        }
        $top = array_values(array_filter($scores, fn (array $row) => $row['rank'] !== null));
        usort($top, fn (array $a, array $b) => $a['rank'] <=> $b['rank']);

        return ['code' => $code, 'scores' => $scores, 'top' => $top, 'majors' => $this->suggestions($code, $majorLimit)];
    }
}
