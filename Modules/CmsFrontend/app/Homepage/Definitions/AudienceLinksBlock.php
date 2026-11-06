<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Liên kết theo đối tượng (sinh viên, giảng viên...). Nội dung khai báo trong cấu hình theme. */
final class AudienceLinksBlock extends AbstractBlockDefinition
{
    protected string $key = 'audience-links';

    protected string $icon = 'ri-team-line';

}
