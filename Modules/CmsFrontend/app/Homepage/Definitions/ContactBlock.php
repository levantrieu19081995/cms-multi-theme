<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm liên hệ cuối trang (chỉ hiện khi chức năng Liên hệ đang bật). */
final class ContactBlock extends AbstractBlockDefinition
{
    protected string $key = 'contact';

    protected string $icon = 'ri-mail-send-line';

}
