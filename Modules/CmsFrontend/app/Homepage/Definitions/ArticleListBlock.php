<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Tin tức nổi bật: bài viết được bật "Hiển thị trang chủ" trong danh sách tin. */
final class ArticleListBlock extends AbstractBlockDefinition
{
    protected string $key = 'article-list';

    protected string $icon = 'ri-newspaper-line';

    protected ?string $route = 'cms.dashboard.cdn.bai-viet.index';


    public function __construct()
    {
        $this->schema = ['news_link' => self::link('cms.dashboard.cdn.bai-viet.index', 'homepage.fields.news_link')];
    }
}
