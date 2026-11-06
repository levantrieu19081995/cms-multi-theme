<?php

namespace Modules\CmsBackend\Exports;

use Closure;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Xuất chi tiết: mỗi lượt hoàn thành một dòng, mỗi câu hỏi một cột. Sinh dòng theo lô để không nạp hết vào bộ nhớ. */
class SurveyDetailExport implements FromGenerator, ShouldAutoSize, WithHeadings, WithTitle
{
    /** @param array<int, string> $headings @param Closure(): \Generator $rows */
    public function __construct(private array $headings, private Closure $rows) {}

    public function generator(): \Generator
    {
        return ($this->rows)();
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return 'Chi tiết';
    }
}
