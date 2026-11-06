<?php

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Normalizer;

/**
 * Old-site (DNN) media for legacy posts, kept in its own folder:
 *   public/media/cms-cu/DesktopModules/CMSP/{HinhUpload,DinhKem,HinhDaiDien1,HinhDaiDien2,Videos}
 *
 * 1. Moves the copied folder there (original structure kept).
 * 2. Rewrites every /DesktopModules/CMSP/... link in posts.content to /media/cms-cu/DesktopModules/CMSP/...
 *    using the real file name on disk (case/encoding exact, so it also works on case-sensitive Linux).
 * 3. Copies each legacy thumbnail (media collection "image") from HinhDaiDien2 (fallback HinhDaiDien1) to the
 *    Spatie layout public/media/cms/{media_id}/{file_name} and switches the collection to "avatars".
 *
 * --dry-run changes nothing (no move, copy or DB write) and writes CSV reports to storage/app/cms-legacy/.
 * Links whose file is not found are left unchanged and reported.
 */
class InstallLegacyMedia extends Command
{
    protected $signature = 'cms:legacy-media {--dry-run : Chỉ đối chiếu và xuất báo cáo, không chuyển/chép/ghi DB}
        {--source= : Thư mục CMSP đang chứa ảnh cũ (mặc định Modules/HinhKhuyenNong/DesktopModules/CMSP)}';
    protected $description = 'Đưa ảnh/tệp website cũ vào public/media/cms-cu, sửa link nội dung bài cũ và ảnh đại diện; chỉ ghi database riêng Khuyến nông.';

    private const PUBLIC_PREFIX = '/media/cms-cu/DesktopModules/CMSP/';
    private const FOLDERS = 'HinhUpload|DinhKem|HinhDaiDien1|HinhDaiDien2|Videos';

    /** lower(normalized relative path) => real relative path */
    private array $paths = [];
    /** folder => lower(normalized file name) => [real relative paths] */
    private array $names = [];
    /** folder => mojibake skeleton of the file name => [real relative paths] */
    private array $skeletons = [];

    public function handle(): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS db')->db;
        if ($database !== config('cmsbackend.editorial_database', 'cms_workspace') || $database === env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang')) {
            $this->error('Database không phải database riêng Khuyến nông. Chưa làm gì.');
            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        $target = public_path('media/cms-cu/DesktopModules/CMSP');
        $source = $this->option('source') ?: base_path('Modules/HinhKhuyenNong/DesktopModules/CMSP');
        if (!is_dir($source) && is_dir($target)) $source = $target; // already moved
        if (!is_dir($source)) {
            $this->error("Không tìm thấy thư mục ảnh cũ: {$source}");
            return self::FAILURE;
        }
        $this->line("Nguồn: {$source}");
        $this->line("Đích:  {$target}" . ($source === $target ? ' (đã ở đúng chỗ)' : ''));
        $this->indexFiles($source);
        $this->line('Đã lập chỉ mục ' . count($this->paths) . ' tệp.');

        [$postUpdates, $linkRows] = $this->planContent();
        [$avatarPlan, $avatarRows] = $this->planAvatars($source);

        $reportDir = storage_path('app/cms-legacy');
        File::ensureDirectoryExists($reportDir);
        $stamp = now()->format('Ymd-His') . ($dryRun ? '-dry-run' : '');
        $this->writeCsv("{$reportDir}/{$stamp}-content-links.csv", ['post_id', 'trang_thai', 'link_cu', 'link_moi', 'ghi_chu'], $linkRows);
        $this->writeCsv("{$reportDir}/{$stamp}-avatars.csv", ['media_id', 'post_id', 'file_name', 'trang_thai', 'nguon', 'dich'], $avatarRows);

        $count = fn (array $rows, int $col, string $value) => count(array_filter($rows, fn ($row) => $row[$col] === $value));
        $this->table(['Hạng mục', 'Số lượng'], [
            ['Bài có link /DesktopModules', count(array_unique(array_column($linkRows, 0)))],
            ['Link tìm thấy tệp (sẽ sửa)', $count($linkRows, 1, 'tim-thay')],
            ['  trong đó tìm theo tên (khác thư mục con)', count(array_filter($linkRows, fn ($row) => $row[1] === 'tim-thay' && $row[4] !== ''))],
            ['Link không tìm thấy tệp (giữ nguyên)', $count($linkRows, 1, 'thieu')],
            ['Bài sẽ cập nhật nội dung', count($postUpdates)],
            ['Ảnh đại diện sẽ chép (HinhDaiDien2)', $count($avatarRows, 3, 'chep-hinhdaidien2')],
            ['Ảnh đại diện sẽ chép (HinhDaiDien1, dự phòng)', $count($avatarRows, 3, 'chep-hinhdaidien1')],
            ['Ảnh đại diện đã có sẵn ở đích', $count($avatarRows, 3, 'da-co')],
            ['Ảnh đại diện không tìm thấy', $count($avatarRows, 3, 'thieu')],
        ]);
        $this->line("Báo cáo: {$reportDir}/{$stamp}-*.csv");

        if ($dryRun) {
            $this->warn('Chạy thử: chưa chuyển thư mục, chưa chép ảnh, chưa ghi database.');
            return self::SUCCESS;
        }

        // 1) Move the folder (same drive: a rename, nothing is duplicated).
        if ($source !== $target) {
            if (is_dir($target)) {
                $this->error("Đích đã tồn tại, không ghi đè: {$target}");
                return self::FAILURE;
            }
            File::ensureDirectoryExists(dirname($target));
            if (!@rename($source, $target)) {
                $this->error('Không chuyển được thư mục (kiểm tra quyền hoặc tệp đang mở).');
                return self::FAILURE;
            }
            $this->info('Đã chuyển thư mục ảnh cũ vào public/media/cms-cu.');
        }
        // 2) Copy thumbnails into the media layout.
        $copied = 0;
        foreach ($avatarPlan as $plan) {
            if ($plan['from'] === null) continue;
            $from = $target . '/' . $plan['from'];
            File::ensureDirectoryExists(dirname($plan['to']));
            if (!is_file($plan['to']) && File::copy($from, $plan['to'])) $copied++;
        }
        $this->info("Đã chép {$copied} ảnh đại diện.");
        // 3) Database: content links + avatar collection, in one transaction.
        DB::transaction(function () use ($postUpdates, $avatarPlan) {
            foreach ($postUpdates as $postId => $content) {
                DB::table('posts')->where('id', $postId)->update(['content' => $content]);
            }
            $ids = array_column(array_filter($avatarPlan, fn ($plan) => $plan['ready']), 'id');
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table('media')->whereIn('id', $chunk)->where('collection_name', 'image')->update(['collection_name' => 'avatars', 'updated_at' => now()]);
            }
        });
        $this->info('Đã cập nhật ' . count($postUpdates) . ' bài và chuyển ảnh đại diện sang collection avatars.');
        return self::SUCCESS;
    }

    private function key(string $value): string
    {
        $value = str_replace('\\', '/', $value);
        $value = preg_replace('#/+#', '/', $value);
        $value = Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
        return mb_strtolower(trim($value, '/'));
    }

    private function indexFiles(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $this->paths[$this->key($relative)] = $relative;
            $folder = explode('/', $relative)[0];
            $this->names[$folder][$this->key($file->getFilename())][] = $relative;
            $this->skeletons[$folder][$this->skeleton($file->getFilename())][] = $relative;
        }
    }

    /** @return array{0: array<int, string>, 1: array<int, array>} post id => new content, CSV rows */
    private function planContent(): array
    {
        $updates = [];
        $rows = [];
        $pattern = '#(?:https?://[^/"\'\s<>]+)?/DesktopModules/CMSP/(' . self::FOLDERS . ')/([^"\'<>]+?)(?=["\'<>])#i';
        foreach (DB::table('posts')->where('content', 'like', '%DesktopModules%')->orderBy('id')->cursor() as $post) {
            $content = preg_replace_callback($pattern, function ($match) use ($post, &$rows) {
                $folder = $this->canonicalFolder($match[1]);
                $raw = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = rawurldecode($raw);
                $relative = $this->paths[$this->key($folder . '/' . $decoded)] ?? null;
                $note = '';
                $wantedDir = $this->key(dirname($folder . '/' . $decoded));
                if ($relative === null) {
                    $candidates = $this->names[$folder][$this->key(basename(str_replace('\\', '/', $decoded)))] ?? [];
                    if ($candidates) {
                        $relative = $this->pick($candidates, $wantedDir);
                        $note = count($candidates) > 1 ? 'theo ten, ' . count($candidates) . ' tep trung ten' : 'theo ten';
                    }
                }
                if ($relative === null) {
                    // File names whose Vietnamese UTF-8 bytes were mangled to CP437 when the folder was copied.
                    $name = Normalizer::normalize(basename(str_replace('\\', '/', $decoded)), Normalizer::FORM_C) ?: basename($decoded);
                    $candidates = $this->skeletons[$folder][$this->skeleton($this->mangle($name))] ?? [];
                    if ($candidates) {
                        $relative = $this->pick($candidates, $wantedDir);
                        $note = 'ten loi font tren dia';
                    }
                }
                if ($relative === null) {
                    $rows[] = [$post->id, 'thieu', $match[0], '', ''];
                    return $match[0];
                }
                $url = self::PUBLIC_PREFIX . implode('/', array_map('rawurlencode', explode('/', $relative)));
                $rows[] = [$post->id, 'tim-thay', $match[0], $url, $note];
                return $url;
            }, $post->content);
            if ($content !== $post->content) $updates[$post->id] = $content;
        }
        return [$updates, $rows];
    }

    /** Prefer originals over DNN "_thumbs" copies, then the referenced sub-folder, then the shortest path. */
    private function pick(array $candidates, string $wantedDir): string
    {
        usort($candidates, function ($a, $b) use ($wantedDir) {
            $score = fn ($path) => [str_contains($path, '/_thumbs/') ? 1 : 0, $this->key(dirname($path)) === $wantedDir ? 0 : 1, strlen($path)];
            return $score($a) <=> $score($b);
        });
        return $candidates[0];
    }

    /** UTF-8 bytes read as CP437 (how the copied folder mangled Vietnamese names). */
    private function mangle(string $utf8): string
    {
        $out = '';
        foreach (str_split($utf8) as $byte) {
            $out .= ord($byte) < 0x80 ? $byte : (iconv('CP437', 'UTF-8', $byte) ?: '?');
        }
        return $out;
    }

    /** Box-drawing/shade characters and the ASCII stand-ins (+ - ¦ _ ¯ |) collapse to one wildcard. */
    private function skeleton(string $name): string
    {
        return preg_replace('/[\x{2500}-\x{259F}+\-¦_¯|]/u', '#', mb_strtolower($name));
    }

    private function canonicalFolder(string $folder): string
    {
        foreach (explode('|', self::FOLDERS) as $name) {
            if (strcasecmp($name, $folder) === 0) return $name;
        }
        return $folder;
    }

    /** @return array{0: array<int, array>, 1: array<int, array>} */
    private function planAvatars(string $source): array
    {
        $plan = [];
        $rows = [];
        $mediaRoot = public_path('media/cms');
        foreach (DB::table('media')->where('collection_name', 'image')->where('model_type', 'App\\Models\\Post')->orderBy('id')->get(['id', 'model_id', 'file_name']) as $media) {
            $to = "{$mediaRoot}/{$media->id}/{$media->file_name}";
            $nameKey = $this->key($media->file_name);
            $from = null;
            $state = 'thieu';
            if (is_file($to)) {
                $state = 'da-co';
            } elseif ($hit = ($this->names['HinhDaiDien2'][$nameKey] ?? null)) {
                $from = $hit[0];
                $state = 'chep-hinhdaidien2';
            } elseif ($hit = ($this->names['HinhDaiDien1'][$nameKey] ?? null)) {
                $from = $hit[0];
                $state = 'chep-hinhdaidien1';
            }
            $plan[] = ['id' => $media->id, 'from' => $from, 'to' => $to, 'ready' => $state !== 'thieu'];
            $rows[] = [$media->id, $media->model_id, $media->file_name, $state, $from ?? '', str_replace(public_path(), 'public', $to)];
        }
        return [$plan, $rows];
    }

    private function writeCsv(string $path, array $header, array $rows): void
    {
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Vietnamese correctly
        fputcsv($handle, $header);
        foreach ($rows as $row) fputcsv($handle, $row);
        fclose($handle);
    }
}
