<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Văn bản, tài liệu như một cụm riêng (cụm lớn). Lọc theo loại/nhóm văn bản; trống = mới nhất. */
final class DocumentsBlock extends AbstractBlockDefinition
{
    protected string $key = 'documents';

    protected string $icon = 'ri-file-text-line';

    protected ?string $route = 'cms.dashboard.cdn.van-ban.index';


    protected array $binding = ['document_filter' => 'cms_home_document_filter'];

    public function __construct()
    {
        $this->schema = [
            'document_filter' => ['type' => 'select', 'label' => 'homepage.fields.document_filter', 'options_source' => 'document_filters', 'default' => ''],
            'documents_link' => self::link('cms.dashboard.cdn.van-ban.index', 'homepage.fields.documents_link'),
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        return ['homepageDocuments' => self::translatedPage() ? collect() : app(\Modules\CmsFrontend\Services\HomeBlocks::class)->documents(self::documentFilter($settings['document_filter'] ?? ''))];
    }
}
