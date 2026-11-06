<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Dải lối tắt tới các chức năng khác (Văn bản, Thư viện ảnh, Dịch vụ, Hỏi đáp). */
final class QuickLinksBlock extends AbstractBlockDefinition
{
    protected string $key = 'quick-links';

    protected string $icon = 'ri-apps-line';

}
