<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm ghép Thư viện ảnh + Video (mục hiển thị trang chủ của từng chức năng). */
final class MediaBlock extends AbstractBlockDefinition
{
    protected string $key = 'media';

    protected string $icon = 'ri-gallery-line';

    protected ?string $route = 'cms.dashboard.cdn.thu-vien-hinh-anh.index';


    public function __construct()
    {
        $this->schema = [
            'gallery_link' => self::link('cms.dashboard.cdn.thu-vien-hinh-anh.index', 'homepage.fields.gallery_link'),
            'video_link' => self::link('video.index', 'homepage.fields.video_link'),
        ];
    }
}
