<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm ghép Thông báo + Văn bản, tài liệu (hai nội dung cùng một khung của theme). */
final class InformationBlock extends AbstractBlockDefinition
{
    protected string $key = 'information';

    protected string $icon = 'ri-information-line';

    protected ?string $route = 'cms.dashboard.cdn.van-ban.index';


    protected array $binding = ['notice_category' => 'web_category_home', 'document_filter' => 'cms_home_document_filter'];

    public function __construct()
    {
        $this->schema = [
            'notice_category' => ['type' => 'category', 'label' => 'homepage.fields.notice_category', 'needed' => true],
            'document_filter' => ['type' => 'select', 'label' => 'homepage.fields.document_filter', 'options_source' => 'document_filters', 'default' => ''],
            'documents_link' => self::link('cms.dashboard.cdn.van-ban.index', 'homepage.fields.documents_link'),
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        $notices = app(\Modules\CmsFrontend\Services\HomeBlocks::class)->noticePosts((int) ($settings['notice_category'] ?? 0));

        return [
            'categoryInfo' => $notices['info'], 'categoryPosts' => $notices['posts'],
            'homepageDocuments' => self::translatedPage() ? collect() : app(\Modules\CmsFrontend\Services\HomeBlocks::class)->documents(self::documentFilter($settings['document_filter'] ?? '')),
        ];
    }
}
