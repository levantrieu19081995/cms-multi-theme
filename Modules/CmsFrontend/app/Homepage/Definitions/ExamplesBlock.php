<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Gương tiêu biểu (theme Khuyến nông đặt tiêu đề "Khuyến nông số"). Do màn hình Gương tiêu biểu quản lý. */
final class ExamplesBlock extends AbstractBlockDefinition
{
    protected string $key = 'examples';

    protected string $icon = 'ri-medal-line';

    protected ?string $route = 'cms.dashboard.guong-tieu-bieu.index';

    public function __construct()
    {
        $this->schema = [
            'examples_link' => self::link('cms.dashboard.guong-tieu-bieu.index', 'homepage.fields.examples_link'),
        ];
    }
}
