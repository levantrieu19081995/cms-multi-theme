<?php

namespace Modules\CmsFrontend\Support;

use App\Models\Post;

/**
 * Where a video post plays from: an uploaded file (collection "videos"), a video file URL in the content
 * (.mp4/.webm/.ogg, absolute or site-relative), or a YouTube link in the content. Used by the homepage
 * block, the video list and the video page so all three agree.
 */
final class VideoSource
{
    private const FILE_PATTERN = '~(?:https?://[^\s"\'<>]+|/[^\s"\'<>]+)\.(?:mp4|webm|ogg|ogv|mov)(?:\?[^\s"\'<>]*)?~i';
    private const YOUTUBE_PATTERN = '~(?:youtube\.com/(?:watch\?(?:[^"\'<>\s]*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~i';

    /** @return array{type: string, url: ?string, youtube_id: ?string, poster: ?string} type: file|youtube|link|none */
    public static function of(Post $video): array
    {
        $poster = trim((string) $video->getFirstMediaUrl('avatars')) ?: null;
        $file = trim((string) $video->getFirstMediaUrl('videos'));
        if ($file !== '') {
            return ['type' => 'file', 'url' => $file, 'youtube_id' => null, 'poster' => $poster];
        }

        $content = html_entity_decode((string) ($video->content ?? ''), ENT_QUOTES);
        $youtubeId = $video->url_id ?? null;
        if (!$youtubeId && preg_match(self::YOUTUBE_PATTERN, $content, $match)) {
            $youtubeId = $match[1];
        }
        if ($youtubeId) {
            return [
                'type' => 'youtube',
                'url' => "https://www.youtube-nocookie.com/embed/{$youtubeId}",
                'youtube_id' => $youtubeId,
                'poster' => $poster ?? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg",
            ];
        }
        if (preg_match(self::FILE_PATTERN, $content, $match)) {
            return ['type' => 'file', 'url' => $match[0], 'youtube_id' => null, 'poster' => $poster];
        }
        $link = trim(strip_tags($content));
        if (preg_match('~^https?://\S+$~i', $link)) {
            return ['type' => 'link', 'url' => $link, 'youtube_id' => null, 'poster' => $poster];
        }

        return ['type' => 'none', 'url' => null, 'youtube_id' => null, 'poster' => $poster];
    }
}
