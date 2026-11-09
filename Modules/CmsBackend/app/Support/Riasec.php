<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsFrontend\Models\CauHoi;

/**
 * Tính điểm RIASEC (mô hình Holland) từ câu trả lời khảo sát. Thuần logic, không truy cập DB.
 *
 * Mỗi câu hỏi (thang điểm hoặc Có/Không) gắn một nhóm R/I/A/S/E/C trong cấu hình câu hỏi (`config.riasec`). Câu trả lời được chuẩn
 * hóa về 0..1 (thang: (giá trị - min) / (max - min); Có = 1, Không = 0), điểm nhóm = trung bình các câu đã trả lời × 100.
 * Mã Holland = tối đa 3 nhóm điểm cao nhất; bằng điểm thì theo thứ tự R, I, A, S, E, C.
 */
final class Riasec
{
    public const LETTERS = ['R', 'I', 'A', 'S', 'E', 'C'];

    public const CODE_LENGTH = 3;

    public static function isLetter(mixed $value): bool
    {
        return is_string($value) && in_array($value, self::LETTERS, true);
    }

    /** Chuẩn hóa mã nhập tay: viết hoa, bỏ ký tự lạ và chữ lặp, giữ tối đa 3 chữ theo thứ tự nhập. */
    public static function normalizeCode(?string $value): string
    {
        $out = '';
        foreach (str_split(strtoupper((string) $value)) as $char) {
            if (self::isLetter($char) && ! str_contains($out, $char)) {
                $out .= $char;
            }
            if (strlen($out) === self::CODE_LENGTH) {
                break;
            }
        }

        return $out;
    }

    /** Kiểu câu hỏi dùng được để tính điểm RIASEC. */
    public static function supports(SurveyQuestionType $type): bool
    {
        return in_array($type, [SurveyQuestionType::Scale, SurveyQuestionType::YesNo], true);
    }

    /**
     * @param  iterable<CauHoi>  $questions  câu hỏi đang bật của khảo sát
     * @param  array<string, mixed>  $answers  khóa = mã câu hỏi (ch_ma)
     * @return array<string, array{questions: int, answered: int, sum: float, pct: ?int}> đủ 6 nhóm
     */
    public static function score(iterable $questions, array $answers): array
    {
        $scores = [];
        foreach (self::LETTERS as $letter) {
            $scores[$letter] = ['questions' => 0, 'answered' => 0, 'sum' => 0.0, 'pct' => null];
        }
        foreach ($questions as $q) {
            $letter = $q->ch_cau_hinh['riasec'] ?? null;
            if (! self::isLetter($letter) || ! self::supports($q->kieu())) {
                continue;
            }
            $scores[$letter]['questions']++;
            $value = self::normalized($q, $answers[$q->ch_ma] ?? null);
            if ($value !== null) {
                $scores[$letter]['answered']++;
                $scores[$letter]['sum'] += $value;
            }
        }
        foreach ($scores as $letter => $row) {
            $scores[$letter]['pct'] = $row['answered'] > 0 ? (int) round($row['sum'] / $row['answered'] * 100) : null;
        }

        return $scores;
    }

    /** Giá trị 0..1 của một câu trả lời; null nếu không trả lời hoặc không hợp lệ. */
    private static function normalized(object $question, mixed $raw): ?float
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }
        if ($question->kieu() === SurveyQuestionType::YesNo) {
            return $raw === 'yes' ? 1.0 : ($raw === 'no' ? 0.0 : null);
        }
        $config = is_array($question->ch_cau_hinh) ? $question->ch_cau_hinh : [];
        $min = (int) ($config['min'] ?? 1);
        $max = (int) ($config['max'] ?? 5);
        if (! is_numeric($raw) || $max <= $min) {
            return null;
        }

        return max(0.0, min(1.0, ((float) $raw - $min) / ($max - $min)));
    }

    /**
     * Mã Holland từ điểm các nhóm; null nếu chưa có câu nào được trả lời.
     *
     * @param  array<string, array{pct: ?int}>  $scores
     */
    public static function holland(array $scores): ?string
    {
        $answered = [];
        foreach (self::LETTERS as $order => $letter) {
            $pct = $scores[$letter]['pct'] ?? null;
            if ($pct !== null) {
                $answered[] = ['letter' => $letter, 'pct' => $pct, 'order' => $order];
            }
        }
        if ($answered === []) {
            return null;
        }
        usort($answered, fn (array $a, array $b) => [$b['pct'], $a['order']] <=> [$a['pct'], $b['order']]);

        return implode('', array_map(fn (array $row) => $row['letter'], array_slice($answered, 0, self::CODE_LENGTH)));
    }

    /**
     * Độ phù hợp giữa mã Holland của người làm và mã của một ngành: mỗi chữ trùng được (3 - vị trí ở mã người làm) × (3 - vị trí ở mã ngành).
     */
    public static function affinity(string $userCode, string $majorCode): int
    {
        $total = 0;
        foreach (str_split($userCode) as $i => $letter) {
            $j = strpos($majorCode, $letter);
            if ($j !== false) {
                $total += (self::CODE_LENGTH - $i) * (self::CODE_LENGTH - $j);
            }
        }

        return $total;
    }
}
