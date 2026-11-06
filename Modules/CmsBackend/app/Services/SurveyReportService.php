<?php

namespace Modules\CmsBackend\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\CmsBackend\DataProviders\SurveyResponseDataProvider;
use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsBackend\Exports\SurveyArraySheet;
use Modules\CmsBackend\Exports\SurveyDetailExport;
use Modules\CmsBackend\Exports\SurveySummaryExport;
use Modules\CmsBackend\Support\SurveyAudit;
use Modules\CmsFrontend\Models\CauHoi;
use Modules\CmsFrontend\Models\KetQua;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;

/**
 * Phản hồi, thống kê và xuất dữ liệu của khảo sát. Một bộ lọc chung (normalizeFilters) cho danh sách, thống kê và xuất để
 * "áp dụng bộ lọc hiện tại" luôn nhất quán.
 */
class SurveyReportService
{
    public function __construct(private SurveyResponseDataProvider $data) {}

    /** @return array{q: string, status: ?string, from: ?string, to: ?string, anonymous: ?string} */
    public function normalizeFilters(array $input): array
    {
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $v) === 1 && strtotime($v) !== false ? $v : null;
        $from = $date($input['from'] ?? null);
        $to = $date($input['to'] ?? null);
        if ($from && $to && $to < $from) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => Str::limit(trim((string) ($input['q'] ?? '')), 100, ''),
            'status' => in_array($input['status'] ?? null, [KhaoSatUser::STATUS_COMPLETED, KhaoSatUser::STATUS_IN_PROGRESS], true) ? $input['status'] : null,
            'from' => $from,
            'to' => $to,
            'anonymous' => in_array($input['anonymous'] ?? null, ['0', '1'], true) ? $input['anonymous'] : null,
        ];
    }

    public function list(KhaoSat $survey, array $filters): LengthAwarePaginator
    {
        return $this->data->paginateParticipations($survey->ks_id, $filters);
    }

    public function deleteParticipation(KhaoSatUser $participation): void
    {
        $this->data->softDeleteParticipation($participation);
        SurveyAudit::record('response_delete', (int) $participation->ks_id, (int) $participation->ksu_id, ['completed' => $participation->isCompleted()]);
    }

    public static function displayName(KhaoSatUser $p): string
    {
        return $p->ksu_an_danh || trim((string) $p->ksu_ho_ten) === '' ? __('admin.survey_report.anonymous') : $p->ksu_ho_ten;
    }

    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return $seconds >= 3600 ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    // ------------------------------------------------------------------ chi tiết

    /**
     * @return array<int, array{group: ?string, question: string, type: string, retired: bool, answer: string, values: array<int, string>}>
     */
    public function detail(KhaoSatUser $participation): array
    {
        $byQuestion = $this->data->answersOf($participation)->groupBy('ch_id');
        $rows = [];
        foreach ($byQuestion as $answers) {
            /** @var CauHoi|null $q */
            $q = $answers->first()->cauHoi;
            $values = $answers->map(fn (KetQua $a) => $this->answerText($q, $a))->filter(fn ($v) => $v !== '')->values()->all();
            $rows[] = [
                'group' => $q?->nhom?->nhom_ten,
                'question' => $q?->ch_noidung ?? __('admin.survey_report.question_missing'),
                'type' => $q ? $q->kieu()->label() : '',
                'retired' => (bool) $q?->trashed(),
                'answer' => implode('; ', $values),
                'values' => $values,
            ];
        }

        return $rows;
    }

    private function answerText(?CauHoi $q, KetQua $a): string
    {
        if ($q === null) {
            return (string) $a->kskq_trl_value;
        }

        return match ($q->kieu()) {
            SurveyQuestionType::Single, SurveyQuestionType::Multiple => (string) ($a->luaChon?->trl_noidung ?? $a->kskq_trl_value),
            SurveyQuestionType::YesNo => $a->kskq_trl_value === 'yes' ? __('survey.form.yes') : ($a->kskq_trl_value === 'no' ? __('survey.form.no') : (string) $a->kskq_trl_value),
            SurveyQuestionType::Text, SurveyQuestionType::Textarea => (string) ($a->kskq_van_ban ?? $a->kskq_trl_value),
            SurveyQuestionType::Scale => (string) $a->kskq_trl_value,
        };
    }

    // ------------------------------------------------------------------ thống kê

    /**
     * @return array{overview: array<string, mixed>, daily: array<int, array<string, mixed>>, questions: array<int, array<string, mixed>>}
     */
    public function statistics(KhaoSat $survey, array $filters): array
    {
        $range = ['from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null];
        $o = $this->data->overview($survey->ks_id, $range);
        $overview = $o + [
            'rate' => $o['started'] > 0 ? round($o['completed'] / $o['started'] * 100, 1) : 0.0,
            'avg_label' => self::duration($o['avg_seconds'] === null ? null : (int) round($o['avg_seconds'])),
        ];

        $daily = $this->data->daily($survey->ks_id, $range);
        $max = max(1, ...array_map(fn ($d) => $d['started'], $daily ?: [['started' => 1]]));
        foreach ($daily as &$d) {
            $d['started_pct'] = round($d['started'] / $max * 100);
            $d['completed_pct'] = round($d['completed'] / $max * 100);
        }
        unset($d);

        $counts = $this->data->answerCounts($survey->ks_id, $range);
        $questions = [];
        foreach ($this->data->questionsForReport($survey->ks_id) as $q) {
            $questions[] = $this->questionStats($q, $counts);
        }

        return ['overview' => $overview, 'daily' => $daily, 'questions' => $questions];
    }

    /** @return array<string, mixed> */
    private function questionStats(CauHoi $q, array $counts): array
    {
        $type = $q->kieu();
        $respondents = $counts['respondents'][$q->ch_id] ?? 0;
        $pct = fn (int $n) => $respondents > 0 ? round($n / $respondents * 100, 1) : 0.0;
        $rows = [];
        $average = null;

        if ($type->hasOptions()) {
            foreach ($q->traLoi as $opt) {
                $n = $counts['options'][$q->ch_id][$opt->trl_id] ?? 0;
                if ($opt->trashed() && $n === 0) {
                    continue;
                }
                $rows[] = ['label' => $opt->trl_noidung, 'count' => $n, 'percent' => $pct($n), 'retired' => $opt->trashed()];
            }
        } elseif ($type === SurveyQuestionType::YesNo) {
            foreach (['yes' => __('survey.form.yes'), 'no' => __('survey.form.no')] as $value => $label) {
                $n = $counts['values'][$q->ch_id][$value] ?? 0;
                $rows[] = ['label' => $label, 'count' => $n, 'percent' => $pct($n), 'retired' => false];
            }
        } elseif ($type === SurveyQuestionType::Scale) {
            $cfg = $q->ch_cau_hinh ?? [];
            $min = (int) ($cfg['min'] ?? 1);
            $max = (int) ($cfg['max'] ?? 5);
            $sum = 0;
            $n = 0;
            for ($v = $min; $v <= $max; $v++) {
                $c = $counts['values'][$q->ch_id][(string) $v] ?? 0;
                $rows[] = ['label' => (string) $v, 'count' => $c, 'percent' => $pct($c), 'retired' => false];
                $sum += $v * $c;
                $n += $c;
            }
            $average = $n > 0 ? round($sum / $n, 2) : null;
        }

        return [
            'id' => $q->ch_id,
            'group' => $q->nhom?->nhom_ten,
            'text' => $q->ch_noidung,
            'type' => $type->value,
            'type_label' => $type->label(),
            'retired' => $q->trashed(),
            'respondents' => $respondents,
            'rows' => $rows,
            'average' => $average,
            'text_count' => $counts['texts'][$q->ch_id] ?? 0,
            'max' => max(1, ...array_column($rows ?: [['count' => 1]], 'count')),
        ];
    }

    // ------------------------------------------------------------------ xuất Excel

    /**
     * Chống "công thức Excel" từ dữ liệu người dùng nhập: ô bắt đầu bằng = + - @ hoặc tab/CR được thêm dấu nháy đơn.
     */
    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /** Xuất tổng hợp (theo bộ lọc). Không chứa dữ liệu cá nhân. */
    public function summaryExport(KhaoSat $survey, array $filters): SurveySummaryExport
    {
        $stats = $this->statistics($survey, $filters);
        $o = $stats['overview'];
        $t = fn (string $k, array $r = []) => __('admin.survey_report.export.'.$k, $r);

        $overview = new SurveyArraySheet($t('sheet_overview'), [$t('metric'), $t('value')], [
            [$t('survey'), self::cell($survey->ks_ten)],
            [$t('range'), ($filters['from'] ?: '…').' → '.($filters['to'] ?: '…')],
            [$t('started'), $o['started']],
            [$t('completed'), $o['completed']],
            [$t('rate'), $o['rate'].'%'],
            [$t('avg_duration'), $o['avg_label']],
        ]);
        $daily = new SurveyArraySheet($t('sheet_daily'), [$t('day'), $t('started'), $t('completed')],
            array_map(fn ($d) => [$d['day'], $d['started'], $d['completed']], $stats['daily']));

        $rows = [];
        foreach ($stats['questions'] as $q) {
            $base = [self::cell($q['group'] ?? ''), self::cell($q['text']), $q['type_label'], $q['respondents']];
            if ($q['rows'] === []) {
                $rows[] = array_merge($base, ['', $q['text_count'], '']);

                continue;
            }
            foreach ($q['rows'] as $r) {
                $rows[] = array_merge($base, [self::cell($r['label']), $r['count'], $r['percent'].'%']);
            }
            if ($q['average'] !== null) {
                $rows[] = array_merge($base, [$t('average'), $q['average'], '']);
            }
        }
        $questions = new SurveyArraySheet($t('sheet_questions'), [$t('group'), $t('question'), $t('type'), $t('respondents'), $t('option'), $t('count'), $t('percent')], $rows);

        return new SurveySummaryExport([$overview, $daily, $questions]);
    }

    /**
     * Xuất chi tiết (theo bộ lọc, chỉ lượt đã hoàn thành). Mặc định KHÔNG gồm họ tên/điện thoại/email; $includeContact mới thêm.
     * Không bao giờ xuất dấu vết trình duyệt, IP băm hay user-agent.
     */
    public function detailExport(KhaoSat $survey, array $filters, bool $includeContact): SurveyDetailExport
    {
        $t = fn (string $k) => __('admin.survey_report.export.'.$k);
        $questions = $this->data->questionsForReport($survey->ks_id);
        $headings = [$t('code'), $t('anonymous'), $t('started_at'), $t('completed_at'), $t('duration')];
        if ($includeContact) {
            array_push($headings, $t('full_name'), $t('phone'), $t('email'));
        }
        foreach ($questions as $q) {
            $headings[] = self::cell(trim(($q->nhom?->nhom_ten ? $q->nhom->nhom_ten.' / ' : '').$q->ch_noidung));
        }
        $filters['status'] = KhaoSatUser::STATUS_COMPLETED;

        $rows = function () use ($survey, $filters, $includeContact, $questions) {
            $chunk = [];
            foreach ($this->data->participationsQuery($survey->ks_id, $filters)->lazyById(500, 'ksu_id') as $participation) {
                $chunk[] = $participation;
                if (count($chunk) < 500) {
                    continue;
                }
                yield from $this->detailRows($chunk, $questions, $includeContact);
                $chunk = [];
            }
            if ($chunk !== []) {
                yield from $this->detailRows($chunk, $questions, $includeContact);
            }
        };

        return new SurveyDetailExport($headings, $rows);
    }

    /** @param array<int, KhaoSatUser> $participations */
    private function detailRows(array $participations, Collection $questions, bool $includeContact): \Generator
    {
        $answers = $this->data->answersByParticipation(array_map(fn ($p) => $p->ksu_id, $participations));
        foreach ($participations as $p) {
            $row = [
                $p->ksu_ma,
                $p->ksu_an_danh ? __('admin.survey_report.yes') : __('admin.survey_report.no'),
                $p->ksu_created_at?->format('Y-m-d H:i:s'),
                $p->ksu_hoan_thanh_at?->format('Y-m-d H:i:s'),
                self::duration($p->ksu_thoi_luong),
            ];
            if ($includeContact) {
                array_push($row, self::cell($p->ksu_ho_ten), self::cell($p->ksu_so_dien_thoai), self::cell($p->ksu_email));
            }
            $byQuestion = ($answers[$p->ksu_id] ?? collect())->groupBy('ch_id');
            foreach ($questions as $q) {
                $row[] = self::cell(($byQuestion[$q->ch_id] ?? collect())->map(fn (KetQua $a) => $this->answerText($q, $a))->filter(fn ($v) => $v !== '')->implode('; '));
            }
            yield $row;
        }
    }
}
