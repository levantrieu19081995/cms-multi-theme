<?php

namespace Modules\CmsBackend\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Xuất tổng hợp: trang "Tổng quan", "Theo ngày", "Theo câu hỏi". */
class SurveySummaryExport implements WithMultipleSheets
{
    /** @param array<int, SurveyArraySheet> $sheets */
    public function __construct(private array $sheets) {}

    public function sheets(): array
    {
        return $this->sheets;
    }
}
