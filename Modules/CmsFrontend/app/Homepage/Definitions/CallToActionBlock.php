<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Lời kêu gọi hành động dẫn tới trang Liên hệ (chỉ hiện khi chức năng Liên hệ đang bật). */
final class CallToActionBlock extends AbstractBlockDefinition
{
    protected string $key = 'cta';

    protected string $icon = 'ri-megaphone-line';

}
