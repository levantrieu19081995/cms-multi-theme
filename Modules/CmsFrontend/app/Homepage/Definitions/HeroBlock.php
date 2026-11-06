<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm đầu trang (banner/hero). Nội dung do màn hình Banner quản lý. */
final class HeroBlock extends AbstractBlockDefinition
{
    protected string $key = 'hero';

    protected string $icon = 'ri-slideshow-line';

    protected ?string $route = 'cms.dashboard.cdn.banner.index';


    public function __construct()
    {
        $this->schema = ['banner_link' => self::link('cms.dashboard.cdn.banner.index', 'homepage.fields.banner_link')];
    }
}
