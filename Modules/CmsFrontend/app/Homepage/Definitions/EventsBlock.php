<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Sự kiện nổi bật. Sự kiện bật "Hiển thị trang chủ" trong danh sách sự kiện. */
final class EventsBlock extends AbstractBlockDefinition
{
    protected string $key = 'events';

    protected string $icon = 'ri-calendar-event-line';

    protected ?string $route = 'cms.dashboard.su-kien.index';

    public function __construct()
    {
        $this->schema = [
            'events_link' => self::link('cms.dashboard.su-kien.index', 'homepage.fields.events_link'),
        ];
    }
}
