<?php

declare(strict_types=1);

namespace Tests\Unit\Career;

use Modules\CmsBackend\Support\Riasec;
use Modules\CmsFrontend\Models\CauHoi;
use PHPUnit\Framework\TestCase;

/** Tính điểm RIASEC và xếp hạng ngành: thuần logic, không cần database. */
final class RiasecTest extends TestCase
{
    private function scale(string $key, string $letter, int $min = 1, int $max = 5): CauHoi
    {
        return new CauHoi(['ch_ma' => $key, 'ch_type' => 'scale', 'ch_trang_thai' => true, 'ch_cau_hinh' => ['min' => $min, 'max' => $max, 'riasec' => $letter]]);
    }

    private function yesNo(string $key, string $letter): CauHoi
    {
        return new CauHoi(['ch_ma' => $key, 'ch_type' => 'yesno', 'ch_trang_thai' => true, 'ch_cau_hinh' => ['riasec' => $letter]]);
    }

    public function test_scale_answers_are_normalised_and_averaged_per_group(): void
    {
        $questions = [$this->scale('a', 'S'), $this->scale('b', 'S'), $this->scale('c', 'I')];
        $scores = Riasec::score($questions, ['a' => '5', 'b' => '3', 'c' => '1']);

        $this->assertSame(75, $scores['S']['pct']);   // (1.0 + 0.5) / 2
        $this->assertSame(0, $scores['I']['pct']);    // giá trị nhỏ nhất = 0%
        $this->assertNull($scores['R']['pct']);       // nhóm không có câu hỏi
        $this->assertSame(2, $scores['S']['answered']);
        $this->assertSame(1, $scores['I']['questions']);
    }

    public function test_yes_no_and_custom_scale_ranges_count(): void
    {
        $questions = [$this->yesNo('y', 'E'), $this->yesNo('n', 'E'), $this->scale('z', 'C', 0, 10)];
        $scores = Riasec::score($questions, ['y' => 'yes', 'n' => 'no', 'z' => '5']);

        $this->assertSame(50, $scores['E']['pct']);
        $this->assertSame(50, $scores['C']['pct']);
    }

    public function test_unanswered_invalid_and_unlabelled_questions_are_ignored(): void
    {
        $unlabelled = new CauHoi(['ch_ma' => 'u', 'ch_type' => 'scale', 'ch_trang_thai' => true, 'ch_cau_hinh' => ['min' => 1, 'max' => 5]]);
        $text = new CauHoi(['ch_ma' => 't', 'ch_type' => 'text', 'ch_trang_thai' => true, 'ch_cau_hinh' => ['riasec' => 'A']]);
        $questions = [$this->scale('a', 'A'), $this->scale('b', 'A'), $unlabelled, $text];
        $scores = Riasec::score($questions, ['a' => '', 'u' => '5', 't' => 'abc', 'b' => 'not-a-number']);

        $this->assertNull($scores['A']['pct']);
        $this->assertSame(2, $scores['A']['questions']);
        $this->assertSame(0, $scores['A']['answered']);
        $this->assertNull(Riasec::holland($scores));
    }

    public function test_out_of_range_values_are_clamped(): void
    {
        $scores = Riasec::score([$this->scale('a', 'R')], ['a' => '99']);
        $this->assertSame(100, $scores['R']['pct']);
    }

    public function test_holland_code_takes_the_three_highest_groups_with_stable_ties(): void
    {
        $questions = [];
        $answers = [];
        foreach (['R' => 1, 'I' => 4, 'A' => 5, 'S' => 5, 'E' => 2, 'C' => 4] as $letter => $value) {
            $questions[] = $this->scale($letter, $letter);
            $answers[$letter] = (string) $value;
        }
        // A và S cùng 100%: theo thứ tự R,I,A,S,E,C nên A đứng trước; I và C cùng 75%: I trước.
        $this->assertSame('ASI', Riasec::holland(Riasec::score($questions, $answers)));
    }

    public function test_holland_code_is_shorter_when_fewer_groups_have_answers(): void
    {
        $scores = Riasec::score([$this->scale('a', 'S'), $this->scale('b', 'R')], ['a' => '5', 'b' => '3']);
        $this->assertSame('SR', Riasec::holland($scores));
    }

    public function test_normalize_code_keeps_valid_unique_letters_up_to_three(): void
    {
        $this->assertSame('IRA', Riasec::normalizeCode(' ira '));
        $this->assertSame('IR', Riasec::normalizeCode('I-R-I'));
        $this->assertSame('SAE', Riasec::normalizeCode('SAEC'));
        $this->assertSame('', Riasec::normalizeCode('xyz 123'));
        $this->assertSame('', Riasec::normalizeCode(null));
    }

    public function test_major_affinity_rewards_matching_letters_and_positions(): void
    {
        $this->assertSame(14, Riasec::affinity('IRA', 'IRA'));    // 3*3 + 2*2 + 1*1
        $this->assertSame(9, Riasec::affinity('IRA', 'I'));
        $this->assertSame(0, Riasec::affinity('IRA', 'SEC'));
        $this->assertGreaterThan(Riasec::affinity('IRA', 'AIR'), Riasec::affinity('IRA', 'IRA'));
    }
}
