<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Đơn vị phối hợp (đối tác). Do màn hình Đối tác quản lý. */
final class PartnersBlock extends AbstractBlockDefinition
{
    protected string $key = 'partners';

    protected string $icon = 'ri-links-line';

    protected ?string $route = 'cms.dashboard.doi-tac.index';

    public function __construct()
    {
        $this->schema = [
            'partners_link' => self::link('cms.dashboard.doi-tac.index', 'homepage.fields.partners_link'),
        ];
    }
}
