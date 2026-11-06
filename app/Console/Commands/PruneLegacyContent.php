<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Option;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Services\EditorialWorkflow;
use Modules\CmsBackend\Services\PostPurger;
use Modules\CmsBackend\Services\StarterContent;
use Modules\CmsBackend\Services\UploadReferences;
use RuntimeException;
use Throwable;

final class PruneLegacyContent extends Command
{
    protected $signature = 'cms:prune-legacy {--apply : Apply the reviewed cleanup} {--backup= : Database dump outside the source} {--created-before=2026-10-21 : Keep all posts created on or after this date}';

    protected $description = 'Keep homepage, design content and linked pages; archive unused copied uploads outside the source.';

    public function handle(StarterContent $starter, PostPurger $purger, UploadReferences $uploads, EditorialWorkflow $workflow): int
    {
        $workflow->assertDatabase();
        if (DB::connection()->getDatabaseName() !== 'cms_workspace') {
            throw new RuntimeException('Cleanup is restricted to the independent cms_workspace database.');
        }
        $cutoff = (string) $this->option('created-before');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $cutoff) || ! strtotime($cutoff)) {
            throw new RuntimeException('Invalid created-before date.');
        }
        $plan = $starter->plan($cutoff);
        $files = $uploads->filesUnder(public_path('media'));
        $referenced = $uploads->referenced($plan['delete']);
        $candidates = array_diff_key($files, $referenced);
        $bytes = array_sum(array_map('filesize', $candidates));
        $summary = ['keep_posts' => count($plan['keep']), 'delete_news' => count($plan['delete']), 'archive_files' => count($candidates), 'archive_bytes' => $bytes, 'created_before' => $cutoff];
        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if (! $this->option('apply')) {
            $this->info('Dry run only. No business records or upload files changed.');

            return self::SUCCESS;
        }
        $backup = realpath((string) $this->option('backup'));
        $base = realpath(base_path());
        if ($backup === false || ! is_file($backup) || filesize($backup) < 1024 || $base === false || str_starts_with($uploads->key($backup), $uploads->key($base).'/')) {
            throw new RuntimeException('A database dump outside the source is required.');
        }
        $header = file_get_contents($backup, false, null, 0, 4096);
        if (! str_contains($header, 'cms_workspace')) {
            throw new RuntimeException('Backup does not identify cms_workspace.');
        }
        $backupRoot = dirname($backup);
        $archiveRoot = $backupRoot.DIRECTORY_SEPARATOR.'removed-uploads';
        if (file_exists($archiveRoot)) {
            throw new RuntimeException('Archive already exists; refusing to overwrite it.');
        }
        mkdir($archiveRoot, 0770, true);
        $manifest = ['database' => DB::connection()->getDatabaseName(), 'source' => $base, 'backup_sha256' => hash_file('sha256', $backup), 'keep_ids' => $plan['keep'], 'delete_ids' => $plan['delete'], 'moved' => [], 'summary' => $summary];
        file_put_contents($backupRoot.'/cleanup-manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $wasDown = app()->isDownForMaintenance();
        $moved = [];
        $committed = false;
        try {
            if (! $wasDown) {
                Artisan::call('down', ['--retry' => 5]);
            }
            DB::transaction(function () use ($plan, $purger, $uploads, $starter, $candidates, $archiveRoot, $base, &$moved): void {
                $purger->deleteRecords($plan['delete']);
                $stillReferenced = $uploads->referenced();
                foreach ($candidates as $key => $original) {
                    if (isset($stillReferenced[$key])) {
                        continue;
                    }
                    $file = $uploads->localFile($original);
                    if ($file === null || ! str_starts_with($uploads->key($file), $uploads->key(public_path('media')).'/')) {
                        throw new RuntimeException('Unsafe upload path; cleanup rolled back.');
                    }
                    $relative = ltrim(substr($file, strlen($base)), '/\\');
                    $destination = $archiveRoot.DIRECTORY_SEPARATOR.$relative;
                    if (! is_dir(dirname($destination))) {
                        mkdir(dirname($destination), 0770, true);
                    }
                    if (! str_starts_with($uploads->key(realpath(dirname($destination))), $uploads->key(realpath($archiveRoot)).'/') || file_exists($destination)) {
                        throw new RuntimeException('Unsafe archive destination.');
                    }
                    $size = filesize($file);
                    if (! rename($file, $destination)) {
                        throw new RuntimeException('Could not archive upload.');
                    }
                    $moved[] = ['source' => $file, 'archive' => $destination, 'bytes' => $size];
                }
                if ($starter->homepageIds() !== $plan['homepage']) {
                    throw new RuntimeException('Homepage content changed; cleanup rolled back.');
                }
                foreach ($stillReferenced as $file) {
                    if (! is_file($file)) {
                        throw new RuntimeException('A retained upload is missing.');
                    }
                }
            });
            $committed = true;
            foreach ($moved as $entry) {
                $uploads->removeEmptyParents($entry['source']);
            }
            Option::query()->updateOrCreate(['key' => 'cms_legacy_pruned_at'], ['value' => now()->toIso8601String(), 'autoload' => 0]);
            $manifest['moved'] = $moved;
            $manifest['completed_at'] = now()->toIso8601String();
            $manifest['summary']['archive_files'] = count($moved);
            $manifest['summary']['archive_bytes'] = array_sum(array_column($moved, 'bytes'));
            $manifest['summary']['remaining_media_bytes'] = array_sum(array_map('filesize', $uploads->filesUnder(public_path('media'))));
            $manifest['summary']['remaining_news'] = Post::query()->where('post_type', 'post')->count();
            file_put_contents($backupRoot.'/cleanup-manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->line(json_encode($manifest['summary'], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        } catch (Throwable $error) {
            foreach ($committed ? [] : array_reverse($moved) as $entry) {
                if (is_file($entry['archive']) && ! file_exists($entry['source'])) {
                    if (! is_dir(dirname($entry['source']))) {
                        mkdir(dirname($entry['source']), 0770, true);
                    }
                    rename($entry['archive'], $entry['source']);
                }
            }
            throw $error;
        } finally {
            if (! $wasDown) {
                Artisan::call('up');
            }
        }
    }
}
