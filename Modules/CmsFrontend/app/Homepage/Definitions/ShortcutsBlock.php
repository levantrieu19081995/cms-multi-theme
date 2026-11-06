<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Lối tắt đầu trang (từ menu chính). Menu do màn hình Menu quản lý. */
final class ShortcutsBlock extends AbstractBlockDefinition
{
    protected string $key = 'shortcuts';

    protected string $icon = 'ri-compass-3-line';

    protected ?string $route = 'menus.index';


    public function __construct()
    {
        $this->schema = ['menu_link' => self::link('menus.index', 'homepage.fields.menu_link')];
    }
}
