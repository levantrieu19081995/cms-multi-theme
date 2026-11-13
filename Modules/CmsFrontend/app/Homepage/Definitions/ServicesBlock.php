<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Dịch vụ đang hoạt động. Do màn hình Dịch vụ quản lý. */
final class ServicesBlock extends AbstractBlockDefinition
{
    protected string $key = 'services';

    protected string $icon = 'ri-customer-service-2-line';

    protected ?string $route = 'cms.dashboard.dich-vu.index';

    public function __construct()
    {
        $this->schema = [
            'services_link' => self::link('cms.dashboard.dich-vu.index', 'homepage.fields.services_link'),
        ];
    }
}
