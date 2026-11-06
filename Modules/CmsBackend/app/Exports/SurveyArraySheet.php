<?php

namespace Modules\CmsBackend\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Một trang tính từ mảng dòng đã được làm sạch (SurveyReportService::cell). */
class SurveyArraySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /** @param array<int, string> $headings @param array<int, array<int, mixed>> $rows */
    public function __construct(private string $title, private array $headings, private array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $this->title) ?? 'Sheet', 0, 31);
    }
}
