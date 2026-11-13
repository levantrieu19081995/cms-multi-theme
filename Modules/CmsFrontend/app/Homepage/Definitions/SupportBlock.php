<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Hỗ trợ và tư vấn: gửi câu hỏi (Hỏi đáp) và liên hệ tư vấn (chỉ khi chức năng Liên hệ đang bật). */
final class SupportBlock extends AbstractBlockDefinition
{
    protected string $key = 'support';

    protected string $icon = 'ri-question-answer-line';

    protected ?string $route = 'cms.dashboard.hoi-dap.index';

    public function __construct()
    {
        $this->schema = [
            'support_link' => self::link('cms.dashboard.hoi-dap.index', 'homepage.fields.support_link'),
        ];
    }
}
