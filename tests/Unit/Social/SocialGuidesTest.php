<?php

declare(strict_types=1);

namespace Tests\Unit\Social;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Http\Controllers\SocialGuideController;
use Modules\CmsBackend\Services\Social\SocialGuides;
use Modules\CmsBackend\Support\ProjectContext;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The guides ("Hướng dẫn"): the .html files of chung/ (every project) and of the folder of the current project, listed by their
 * title, shown as they are and never anything else. A temporary folder is used; no database.
 */
final class SocialGuidesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'guides-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
        foreach (['chung', 'nam-binh-duong', 'ban-de-an'] as $sub) {
            mkdir($this->dir.DIRECTORY_SEPARATOR.$sub);
        }
    }

    protected function tearDown(): void
    {
        foreach (['chung', 'nam-binh-duong', 'ban-de-an'] as $sub) {
            foreach (glob($this->dir.DIRECTORY_SEPARATOR.$sub.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->dir.DIRECTORY_SEPARATOR.$sub);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function writeGuide(string $folder, string $name, string $content): void
    {
        file_put_contents($this->dir.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$name, $content);
    }

    private function guides(string $project = 'nam-binh-duong'): SocialGuides
    {
        return new SocialGuides($this->dir, $project);
    }

    private function controller(string $project = 'nam-binh-duong'): SocialGuideController
    {
        return new SocialGuideController(app(SocialPublicationDataProvider::class), $this->guides($project));
    }

    public function test_lists_only_html_files_by_title_in_alphabetical_order(): void
    {
        $this->writeGuide('chung', 'b-second.html', '<!doctype html><html><head><title>Bước hai &amp; ba</title></head><body>2</body></html>');
        $this->writeGuide('chung', 'a-first.html', '<html><head><title>  Alpha   guide </title></head><body>1</body></html>');
        $this->writeGuide('chung', 'notes.txt', 'not a guide');
        $this->writeGuide('chung', 'page.html.bak', '<title>No</title>');
        $this->writeGuide('chung', 'bad name.html', '<title>Space in the name</title>');

        $guides = $this->guides()->forScope(SocialGuides::SCOPE_COMMON);

        $this->assertSame(['a-first.html', 'b-second.html'], array_column($guides, 'file'));
        $this->assertSame(['Alpha guide', 'Bước hai & ba'], array_column($guides, 'title'));
        $this->assertSame([SocialGuides::SCOPE_COMMON, SocialGuides::SCOPE_COMMON], array_column($guides, 'scope'));
        $this->assertGreaterThan(0, $guides[0]['size']);
    }

    public function test_a_project_sees_the_common_guides_and_only_its_own_folder(): void
    {
        $this->writeGuide('chung', 'zalo.html', '<title>Zalo</title>');
        $this->writeGuide('nam-binh-duong', 'nbd.html', '<title>NBD only</title>');
        $this->writeGuide('ban-de-an', 'bda.html', '<title>BDA only</title>');

        $nbd = $this->guides('nam-binh-duong')->all();
        $bda = $this->guides('ban-de-an')->all();

        $this->assertSame(['chung/zalo.html', 'du-an/nbd.html'], array_map(fn ($g) => $g['scope'].'/'.$g['file'], $nbd));
        $this->assertSame(['chung/zalo.html', 'du-an/bda.html'], array_map(fn ($g) => $g['scope'].'/'.$g['file'], $bda));
        $this->assertNull($this->guides('nam-binh-duong')->find('du-an', 'bda.html'), "Another project's guide is not reachable.");
    }

    public function test_the_same_file_name_can_exist_in_both_folders(): void
    {
        $this->writeGuide('chung', 'intro.html', '<title>Common intro</title>');
        $this->writeGuide('nam-binh-duong', 'intro.html', '<title>Project intro</title>');

        $this->assertSame('Common intro', $this->guides()->find('chung', 'intro.html')['title']);
        $this->assertSame('Project intro', $this->guides()->find('du-an', 'intro.html')['title']);
    }

    public function test_without_a_usable_project_only_the_common_guides_are_listed(): void
    {
        $this->writeGuide('chung', 'zalo.html', '<title>Zalo</title>');
        $this->writeGuide('nam-binh-duong', 'nbd.html', '<title>NBD</title>');

        foreach (['', '../x', 'chung', 'Nam Binh Duong'] as $key) {
            $guides = $this->guides($key);
            $this->assertNull($guides->projectKey(), $key);
            $this->assertSame(['zalo.html'], array_column($guides->all(), 'file'), $key);
            $this->assertNull($guides->find('du-an', 'nbd.html'), $key);
        }
    }

    public function test_a_document_without_a_title_is_named_after_its_file(): void
    {
        $this->writeGuide('chung', 'huong_dan-ket_noi.html', '<p>fragment without head</p>');

        $this->assertSame('Huong dan ket noi', $this->guides()->all()[0]['title']);
    }

    public function test_a_name_is_only_accepted_when_the_folder_lists_it(): void
    {
        $this->writeGuide('chung', 'ok.html', '<title>Ok</title>');
        $this->writeGuide('ban-de-an', 'secret.html', '<title>Secret</title>');

        $this->assertNotNull($this->guides()->find('chung', 'ok.html'));
        foreach ([
            ['chung', '../ban-de-an/secret.html'], ['chung', '..\\ban-de-an\\secret.html'], ['chung', 'ok.html/../ok.html'], ['chung', '/etc/passwd'],
            ['chung', 'missing.html'], ['chung', 'ok.htm'], ['chung', ''],
            ['ban-de-an', 'secret.html'], ['../ban-de-an', 'secret.html'], ['', 'ok.html'], ['all', 'ok.html'],
        ] as [$scope, $name]) {
            $this->assertNull($this->guides()->find($scope, $name), $scope.'|'.$name);
            $this->assertNull($this->guides()->html($scope, $name), $scope.'|'.$name);
        }
    }

    public function test_html_opens_links_in_a_new_window_for_a_complete_document_only(): void
    {
        $this->writeGuide('chung', 'full.html', '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>T</title></head><body>x</body></html>');
        $this->writeGuide('chung', 'part.html', '<p>fragment</p>');

        $this->assertStringContainsString('<base target="_blank">', (string) $this->guides()->html('chung', 'full.html'));
        $this->assertSame('<p>fragment</p>', $this->guides()->html('chung', 'part.html'));
    }

    public function test_the_raw_document_is_served_with_a_restrictive_policy(): void
    {
        $this->writeGuide('chung', 'doc.html', '<!doctype html><html><head><title>T</title></head><body><script>alert(1)</script>Xin chào</body></html>');

        $response = $this->controller()->raw('chung', 'doc.html');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Xin chào', $response->getContent());
        $this->assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('sandbox', $policy);
        $this->assertStringContainsString("default-src 'none'", $policy);
        $this->assertStringNotContainsString('allow-scripts', $policy, 'No script may run in a guide.');
        $this->assertStringNotContainsString('allow-same-origin', $policy);
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_an_unknown_or_unlisted_guide_is_not_found(): void
    {
        $this->writeGuide('chung', 'ok.html', '<title>Ok</title>');
        $this->writeGuide('ban-de-an', 'bda.html', '<title>BDA</title>');

        foreach (['raw', 'show'] as $method) {
            foreach ([['chung', 'missing.html'], ['du-an', 'bda.html'], ['ban-de-an', 'bda.html']] as [$scope, $file]) {
                try {
                    $this->controller()->{$method}($scope, $file);
                    $this->fail($method.' '.$scope.'/'.$file.' must answer 404.');
                } catch (HttpException $e) {
                    $this->assertSame(404, $e->getStatusCode());
                }
            }
        }
    }

    public function test_a_huge_file_is_not_listed(): void
    {
        $this->writeGuide('chung', 'big.html', str_repeat('x', 2 * 1024 * 1024 + 10));
        $this->writeGuide('chung', 'real.html', '<title>Real</title>');

        $this->assertSame(['real.html'], array_column($this->guides()->all(), 'file'));
    }

    public function test_the_guides_shipped_with_the_module_are_in_the_common_folder(): void
    {
        $guides = new SocialGuides(null, '');

        $this->assertContains('HUONG_DAN_CAU_HINH_ZALO_CHO_KHACH_HANG.html', array_column($guides->forScope(SocialGuides::SCOPE_COMMON), 'file'));
    }

    public function test_every_registered_project_has_a_folder_in_the_module(): void
    {
        foreach (array_keys(ProjectContext::projects()) as $key) {
            $this->assertDirectoryExists(module_path('CmsBackend', 'resources/guides/'.$key), $key);
        }
    }
}
