<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Video bật hiển thị trang chủ. */
final class VideosBlock extends AbstractBlockDefinition
{
    protected string $key = 'videos';

    protected string $icon = 'ri-video-line';

    public function __construct()
    {
        $this->schema = [
            'video_link' => self::link('video.index', 'homepage.fields.video_link'),
        ];
    }
}
