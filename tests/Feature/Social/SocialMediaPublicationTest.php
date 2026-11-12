<?php

declare(strict_types=1);

namespace Tests\Feature\Social;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Tests\TestCase;

/**
 * Several pictures or one video in a publication: stored in order, validated, and sent to Facebook (faked Graph API and faked
 * downloads, SQLite :memory: only).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Social
 */
final class SocialMediaPublicationTest extends TestCase
{
    private const PROJECT = 'nam-binh-duong';

    private const KEY = 'aaaaaaaa-bbbb-cccc-dddd-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }

        config([
            'activitylog.enabled' => false,
            'queue.default' => 'sync',
            'app.url' => 'https://example.test',
            'cmsbackend.projects.key' => self::PROJECT,
            'cmsbackend.social.facebook' => [
                'app_id' => '123456789', 'app_secret' => 'app-secret-value', 'graph_version' => 'v27.0',
                'redirect_uri' => 'https://example.test/cms/truyen-thong/tai-khoan/facebook/callback',
                'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],
                'timeout' => 5, 'connect_timeout' => 2, 'state_ttl' => 600,
            ],
        ]);
        foreach ([
            '2026_10_30_000001_create_social_accounts_table.php',
            '2026_10_30_000002_create_social_publications_table.php',
            '2026_10_30_000003_create_social_publication_channels_table.php',
            '2026_11_11_000001_add_metrics_to_social_publication_channels_table.php',
            '2026_11_12_000001_add_media_to_social_publications_table.php',
        ] as $file) {
            (require base_path('database/migrations/'.$file))->up();
        }
        $this->assertTrue(Schema::hasColumn('social_publications', 'media'));
    }

    private function writer(): User
    {
        $labels = array_map(fn (PermissionsEnum $permission) => $permission->label(), [
            PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_PUBLICATION_CREATE, PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH,
        ]);

        $user = new class($labels) extends User
        {
            public function __construct(private array $labels = [])
            {
                parent::__construct();
            }

            public function can($abilities, $arguments = []): bool
            {
                return in_array($abilities, $this->labels, true);
            }
        };
        $user->forceFill(['id' => 7]);

        return $user;
    }

    private function service(): SocialPublicationService
    {
        return app(SocialPublicationService::class);
    }

    private function connectFacebook(): void
    {
        $account = new SocialAccount([
            'project_key' => self::PROJECT, 'platform' => SocialChannel::FACEBOOK->value, 'name' => 'Page NBD',
            'external_account_id' => '9001', 'status' => SocialAccountStatus::CONNECTED->value,
        ]);
        $account->credentials = ['page_access_token' => 'PAGE-TOKEN'];
        $account->save();
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }

    /** @return array<string, mixed> */
    private function input(array $overrides = []): array
    {
        return $overrides + ['title' => 'Thông báo', 'message' => 'Nội dung', 'channels' => ['facebook'], 'intent' => 'ready', 'idempotency_key' => self::KEY];
    }

    public function test_pictures_are_kept_in_order_and_the_first_one_is_the_cover(): void
    {
        [$publication] = $this->service()->createDirect($this->writer(), $this->input([
            'intent' => 'draft',
            'media' => ['', 'https://example.test/b.jpg', 'https://example.test/a.png', 'https://example.test/b.jpg', 'https://example.test/readme.pdf', '/media/c.gif'],
        ]));

        $items = $publication->fresh()->mediaItems();
        $this->assertSame(['https://example.test/b.jpg', 'https://example.test/a.png', url('/media/c.gif')], array_map(fn ($item) => $item['url'], $items));
        $this->assertSame(['image', 'image', 'image'], array_map(fn ($item) => $item['type'], $items));
        $this->assertSame('https://example.test/b.jpg', $publication->image_url);
    }

    public function test_clearing_the_list_removes_the_pictures(): void
    {
        [$publication] = $this->service()->createDirect($this->writer(), $this->input(['intent' => 'draft', 'media' => ['https://example.test/a.jpg']]));

        $this->service()->updateDraft($this->writer(), $publication, ['title' => 'A', 'media' => [''], 'intent' => 'draft']);

        $fresh = $publication->fresh();
        $this->assertSame([], $fresh->mediaItems());
        $this->assertNull($fresh->image_url);
    }

    public function test_pictures_and_video_cannot_be_mixed_and_the_limits_hold(): void
    {
        $refused = 0;
        foreach ([
            ['https://example.test/a.jpg', 'https://example.test/v.mp4'],
            ['https://example.test/v.mp4', 'https://example.test/w.mp4'],
            array_map(fn ($i) => "https://example.test/$i.jpg", range(1, 11)),
        ] as $index => $media) {
            try {
                $this->service()->createDirect($this->writer(), $this->input([
                    'intent' => 'draft', 'media' => $media, 'idempotency_key' => 'aaaaaaaa-bbbb-cccc-dddd-'.str_pad((string) $index, 12, '0', STR_PAD_LEFT),
                ]));
            } catch (SocialException) {
                $refused++;
            }
        }

        $this->assertSame(3, $refused);
        $this->assertSame(0, SocialPublication::query()->count());
    }

    public function test_a_video_cannot_be_prepared_for_zalo(): void
    {
        $this->expectException(SocialException::class);

        $this->service()->createDirect($this->writer(), $this->input(['channels' => ['zalo'], 'media' => ['https://example.test/v.mp4']]));
    }

    public function test_several_pictures_are_uploaded_unpublished_then_attached_to_one_feed_post(): void
    {
        $this->connectFacebook();
        $graphCalls = 0;
        Http::fake(function (Request $request) use (&$graphCalls) {
            if (str_contains($request->url(), 'graph.facebook.com')) {
                $graphCalls++;

                return Http::response(str_contains($request->url(), '/photos') ? ['id' => 'photo-'.$graphCalls] : ['id' => '9001_900']);
            }

            return Http::response($this->png(), 200, ['Content-Type' => 'image/png']);
        });

        [$publication] = $this->service()->createDirect($this->writer(), $this->input([
            'public_url' => 'https://example.test/bai.htm',
            'media' => ['https://example.test/a.png', 'https://example.test/b.png', 'https://example.test/c.png'],
        ]));
        $this->service()->publish($this->writer(), $publication);

        $delivery = $publication->channels()->first();
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status, (string) $delivery->error_message);
        $this->assertSame('9001_900', $delivery->remote_post_id);
        $this->assertSame(4, $graphCalls);
        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/9001/feed')) {
                return false;
            }
            $data = $request->data();

            return ($data['attached_media[0]'] ?? null) === '{"media_fbid":"photo-1"}'
                && ($data['attached_media[2]'] ?? null) === '{"media_fbid":"photo-3"}'
                && str_ends_with((string) ($data['message'] ?? ''), "\n\nhttps://example.test/bai.htm");
        });
    }

    public function test_a_video_is_downloaded_and_uploaded_to_the_videos_edge(): void
    {
        $this->connectFacebook();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'graph.facebook.com')) {
                return Http::response(['id' => '777']);
            }

            return Http::response("\x00\x00\x00\x18ftypmp42".str_repeat("\x00", 64), 200, ['Content-Type' => 'video/mp4']);
        });

        [$publication] = $this->service()->createDirect($this->writer(), $this->input([
            'public_url' => 'https://example.test/bai.htm', 'media' => ['https://example.test/media/clip.mp4'],
        ]));
        $this->service()->publish($this->writer(), $publication);

        $delivery = $publication->channels()->first();
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status, (string) $delivery->error_message);
        $this->assertSame('777', $delivery->remote_post_id);
        $this->assertSame('https://www.facebook.com/9001/videos/777', $delivery->remote_url);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/9001/videos'));
    }

    public function test_a_file_that_is_not_a_video_fails_the_delivery_and_sends_nothing_to_facebook(): void
    {
        $this->connectFacebook();
        Http::fake(function (Request $request) {
            return str_contains($request->url(), 'graph.facebook.com') ? Http::response(['id' => '1']) : Http::response('<html>not a video</html>', 200);
        });

        [$publication] = $this->service()->createDirect($this->writer(), $this->input([
            'public_url' => 'https://example.test/bai.htm', 'media' => ['https://example.test/media/clip.mp4'],
        ]));
        $this->service()->publish($this->writer(), $publication);

        $this->assertSame(SocialChannelStatus::FAILED, $publication->channels()->first()->status);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'graph.facebook.com'));
    }
}
