<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Các con số nổi bật. Bốn dòng nhãn/giá trị (có bản dịch) nhập ở màn hình Con số nổi bật (FeaturedStatisticController). */
final class StatisticsBlock extends AbstractBlockDefinition
{
    protected string $key = 'statistics';

    protected string $icon = 'ri-bar-chart-box-line';

    protected ?string $route = 'cms.dashboard.con-so-noi-bat.index';


    public function __construct()
    {
        $this->schema = ['statistics_link' => self::link('cms.dashboard.con-so-noi-bat.index', 'homepage.fields.statistics_link')];
    }
}
