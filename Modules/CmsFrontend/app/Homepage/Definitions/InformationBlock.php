<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsFrontend\Services\HomeBlocks;

/**
 * Thông báo theo chuyên mục. Văn bản, tài liệu từng nằm chung cụm này; nay là cụm `documents` tách từ cụm này
 * (bộ lọc văn bản đã lưu ở đây vẫn được cụm documents đọc cho tới khi lưu riêng).
 */
final class InformationBlock extends AbstractBlockDefinition
{
    protected string $key = 'information';

    protected string $icon = 'ri-information-line';

    protected array $binding = ['notice_category' => 'web_category_home'];

    public function __construct()
    {
        $this->schema = [
            'notice_category' => ['type' => 'category', 'label' => 'homepage.fields.notice_category', 'needed' => true],
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        $notices = app(HomeBlocks::class)->noticePosts((int) ($settings['notice_category'] ?? 0));

        return ['categoryInfo' => $notices['info'], 'categoryPosts' => $notices['posts']];
    }
}
