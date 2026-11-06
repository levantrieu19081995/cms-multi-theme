<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Post;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

final class UploadReferences
{
    public function key(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }

    /** @return array<string, string> */
    private function aliases(): array
    {
        $aliases = ['/media' => public_path('media'), '/userfiles' => public_path('userfiles'), '/storage' => storage_path('app/public')];
        foreach (['cms_media', 'media', 'public'] as $name) {
            $disk = config('filesystems.disks.'.$name, []);
            if (($disk['driver'] ?? '') !== 'local' || empty($disk['url']) || empty($disk['root'])) {
                continue;
            }
            $prefix = rtrim((string) parse_url($disk['url'], PHP_URL_PATH), '/');
            if ($prefix !== '') {
                $aliases[$prefix] = $disk['root'];
            }
        }
        uksort($aliases, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $aliases;
    }

    public function localFile(string $path): ?string
    {
        $real = realpath($path);
        $base = realpath(base_path());
        if ($real === false || $base === false || ! is_file($real) || is_link($path) || ! str_starts_with($this->key($real), $this->key($base).'/')) {
            return null;
        }
        foreach ($this->aliases() as $root) {
            $root = realpath($root);
            if ($root !== false && str_starts_with($this->key($real), $this->key($root).'/')) {
                return $real;
            }
        }

        return null;
    }

    public function fromUrl(string $url): ?string
    {
        $url = html_entity_decode(str_replace('\\/', '/', $url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (str_contains($path, "\0") || str_contains($path, '\\') || preg_match('~(?:^|/)\.\.(?:/|$)~', $path)) {
            return null;
        }
        foreach ($this->aliases() as $prefix => $root) {
            if (str_starts_with($path, $prefix.'/')) {
                return $this->localFile($root.DIRECTORY_SEPARATOR.substr($path, strlen($prefix) + 1));
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public function inText(?string $text): array
    {
        $text = html_entity_decode(str_replace('\\/', '/', $text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('~(?:https?://[^\s<>"\x27()\[\]{}]+|/(?:media|storage|userfiles)/[^\s<>"\x27()\[\]{}]+)~iu', $text, $matches);
        $files = [];
        foreach ($matches[0] as $url) {
            $file = $this->fromUrl($url);
            if ($file !== null) {
                $files[$this->key($file)] = $file;
            }
        }

        return $files;
    }

    /** @return array<string, string> */
    public function filesUnder(string $directory): array
    {
        if (! is_dir($directory) || is_link($directory)) {
            return [];
        }
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isLink() || ! $entry->isFile()) {
                continue;
            }
            $file = $this->localFile($entry->getPathname());
            if ($file !== null) {
                $files[$this->key($file)] = $file;
            }
        }

        return $files;
    }

    /** @return array<string, string> */
    public function mediaFiles(Media $media): array
    {
        $files = [];
        $generator = PathGeneratorFactory::create($media);
        foreach (array_unique([$media->disk, $media->conversions_disk ?: $media->disk]) as $diskName) {
            $disk = config('filesystems.disks.'.$diskName, []);
            if (($disk['driver'] ?? '') !== 'local' || empty($disk['root'])) {
                continue;
            }
            $directory = rtrim($disk['root'], '/\\').DIRECTORY_SEPARATOR.$generator->getPath($media);
            $files += $this->filesUnder($directory);
        }

        return $files;
    }

    /** @param array<int, int|string> $excludedPostIds
     * @return array<string, string>
     */
    public function referenced(array $excludedPostIds = []): array
    {
        $files = [];
        $ignored = ['activity_log', 'cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs', 'job_batches', 'visits', 'cms_editorial_history', 'migrations', 'password_reset_tokens'];
        foreach (Schema::getTables() as $tableInfo) {
            $table = $tableInfo['name'];
            if (in_array($table, $ignored, true) || $table === 'media') {
                continue;
            }
            $columns = Schema::getColumns($table);
            $names = array_column($columns, 'name');
            $textColumns = array_column(array_filter($columns, fn (array $column): bool => in_array($column['type_name'], ['varchar', 'char', 'text', 'mediumtext', 'longtext', 'json'], true)), 'name');
            if ($textColumns === []) {
                continue;
            }
            $query = DB::table($table)->select($textColumns);
            if ($excludedPostIds !== []) {
                if ($table === 'posts') {
                    $query->whereNotIn('id', $excludedPostIds);
                } elseif (in_array('post_id', $names, true)) {
                    $query->whereNotIn('post_id', $excludedPostIds);
                } elseif ($table === 'translations') {
                    $query->where(fn ($q) => $q->where('table_name', '!=', 'posts')->orWhereNotIn('object_id', $excludedPostIds));
                }
            }
            foreach ($query->cursor() as $row) {
                foreach ((array) $row as $value) {
                    if (is_string($value)) {
                        $files += $this->inText($value);
                    }
                }
            }
        }
        foreach (Media::query()->when($excludedPostIds !== [], fn ($q) => $q->where(fn ($q) => $q->where('model_type', '!=', (new Post)->getMorphClass())->orWhereNotIn('model_id', $excludedPostIds)))->cursor() as $media) {
            $files += $this->mediaFiles($media);
        }

        return $files;
    }

    public function removeEmptyParents(string $file): void
    {
        $directory = dirname($file);
        $roots = array_filter(array_map('realpath', array_values($this->aliases())));
        while (is_dir($directory) && ! is_link($directory) && ! in_array(realpath($directory), $roots, true)) {
            $real = realpath($directory);
            if ($real === false || ! str_starts_with($this->key($real), $this->key(base_path()).'/') || count(scandir($directory)) > 2 || ! rmdir($directory)) {
                break;
            }
            $directory = dirname($directory);
        }
    }
}
