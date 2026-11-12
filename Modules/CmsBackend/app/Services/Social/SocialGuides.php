<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use Illuminate\Support\Carbon;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * The guides ("Hướng dẫn") of the administration: the .html files of two kinds of folder under Modules/CmsBackend/resources/guides,
 * listed by their <title> and shown as they are.
 *
 *  - chung/        guides for every project;
 *  - <project>/    guides of one project, in a folder named like the project key (nam-binh-duong, ban-de-an, khuyen-nong).
 *
 * A project only ever sees chung/ and its own folder: the project is the current one (ProjectContext), never a value of a request.
 * A request names a scope ("chung" or "du-an" = the current project) and a file; the file is looked up in the listing of that
 * folder, never joined to a path, so no "../", no other extension, no symbolic link and no other project's folder can be reached.
 * To add a guide, put a .html file in the right folder.
 */
final class SocialGuides
{
    public const SCOPE_COMMON = 'chung';

    public const SCOPE_PROJECT = 'du-an';

    /** For route constraints. */
    public const SCOPE_PATTERN = 'chung|du-an';

    /** Names that may exist at all: letters, digits, dot, dash and underscore, ending in .html. */
    public const NAME_PATTERN = '[A-Za-z0-9._-]+\.html';

    private const MAX_BYTES = 2 * 1024 * 1024;

    private readonly string $directory;

    /**
     * @param  string|null  $directory  folder that holds chung/ and the project folders (default: the module's resources/guides)
     * @param  string|null  $projectKey  project to show (default: the current project; '' = no project)
     */
    public function __construct(?string $directory = null, private readonly ?string $projectKey = null)
    {
        $this->directory = rtrim($directory ?? module_path('CmsBackend', 'resources/guides'), '/\\');
    }

    /** Key of the project whose folder is shown, or null (unknown project, or a name that cannot be a folder). */
    public function projectKey(): ?string
    {
        $key = $this->projectKey;
        if ($key === null) {
            try {
                $key = ProjectContext::key();
            } catch (\Throwable) {
                return null;
            }
        }

        return preg_match('/^[a-z0-9-]+$/', $key) === 1 && $key !== self::SCOPE_COMMON ? $key : null;
    }

    /** @return array<int, array{scope: string, file: string, title: string, size: int, modified: Carbon}> by title */
    public function forScope(string $scope): array
    {
        $folder = $this->folder($scope);
        if ($folder === null) {
            return [];
        }

        $guides = [];
        foreach (glob($folder.DIRECTORY_SEPARATOR.'*.html') ?: [] as $path) {
            $file = basename($path);
            if (! $this->usable($file, $path)) {
                continue;
            }
            $guides[] = [
                'scope' => $scope,
                'file' => $file,
                'title' => $this->title($path, $file),
                'size' => (int) filesize($path),
                'modified' => Carbon::createFromTimestamp((int) filemtime($path), config('app.timezone')),
            ];
        }
        usort($guides, fn (array $a, array $b) => strcmp(mb_strtolower($a['title']), mb_strtolower($b['title'])) ?: strcmp($a['file'], $b['file']));

        return $guides;
    }

    /** @return array<int, array{scope: string, file: string, title: string, size: int, modified: Carbon}> common guides first, then the project's */
    public function all(): array
    {
        return array_merge($this->forScope(self::SCOPE_COMMON), $this->forScope(self::SCOPE_PROJECT));
    }

    /** @return array{scope: string, file: string, title: string, size: int, modified: Carbon}|null */
    public function find(string $scope, string $file): ?array
    {
        foreach ($this->forScope($scope) as $guide) {
            if ($guide['file'] === $file) {
                return $guide;
            }
        }

        return null;
    }

    /** The HTML of a listed guide, or null. Links open in a new window (a <base> is added to a complete document). */
    public function html(string $scope, string $file): ?string
    {
        if ($this->find($scope, $file) === null) {
            return null;
        }
        $html = file_get_contents($this->folder($scope).DIRECTORY_SEPARATOR.$file);
        if ($html === false) {
            return null;
        }

        return preg_replace('~<head\b[^>]*>~i', '$0<base target="_blank">', $html, 1) ?? $html;
    }

    /** Folder of a scope, or null for an unknown scope or when there is no project. */
    private function folder(string $scope): ?string
    {
        $name = match ($scope) {
            self::SCOPE_COMMON => self::SCOPE_COMMON,
            self::SCOPE_PROJECT => $this->projectKey(),
            default => null,
        };

        return $name === null ? null : $this->directory.DIRECTORY_SEPARATOR.$name;
    }

    private function usable(string $file, string $path): bool
    {
        return preg_match('~^'.self::NAME_PATTERN.'$~', $file) === 1
            && is_file($path) && ! is_link($path)
            && (int) filesize($path) <= self::MAX_BYTES;
    }

    private function title(string $path, string $file): string
    {
        $head = (string) file_get_contents($path, false, null, 0, 8192);
        if (preg_match('~<title[^>]*>(.*?)</title>~is', $head, $match) === 1) {
            $title = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($title !== '') {
                return mb_substr($title, 0, 150);
            }
        }

        return ucfirst(trim(str_replace(['_', '-'], ' ', pathinfo($file, PATHINFO_FILENAME))));
    }
}
