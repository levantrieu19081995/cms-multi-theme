<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Thư viện hình ảnh: album bật hiển thị trang chủ. */
final class GalleryBlock extends AbstractBlockDefinition
{
    protected string $key = 'gallery';

    protected string $icon = 'ri-gallery-line';

    protected ?string $route = 'cms.dashboard.cdn.thu-vien-hinh-anh.index';

    public function __construct()
    {
        $this->schema = [
            'gallery_link' => self::link('cms.dashboard.cdn.thu-vien-hinh-anh.index', 'homepage.fields.gallery_link'),
        ];
    }
}
