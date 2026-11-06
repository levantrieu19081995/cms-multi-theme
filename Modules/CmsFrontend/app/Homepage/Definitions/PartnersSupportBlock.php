<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm Đối tác / liên kết (và dịch vụ hỗ trợ). Do màn hình Đối tác quản lý. */
final class PartnersSupportBlock extends AbstractBlockDefinition
{
    protected string $key = 'partners-support';

    protected string $icon = 'ri-links-line';

    protected ?string $route = 'cms.dashboard.doi-tac.index';


    public function __construct()
    {
        $this->schema = [
            'partners_link' => self::link('cms.dashboard.doi-tac.index', 'homepage.fields.partners_link'),
            'services_link' => self::link('cms.dashboard.dich-vu.index', 'homepage.fields.services_link'),
        ];
    }
}
