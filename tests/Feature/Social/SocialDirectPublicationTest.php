<?php

declare(strict_types=1);

namespace Tests\Feature\Social;

use App\Models\User;
use App\Services\LocaleManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Tests\TestCase;

/**
 * A publication written directly in the drawer of "Tạo bài đăng" (no source post, optional address). Faked Graph API and
 * SQLite :memory: only.
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Social
 */
final class SocialDirectPublicationTest extends TestCase
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
        ] as $file) {
            (require base_path('database/migrations/'.$file))->up();
        }
        $this->assertTrue(Schema::hasColumn('social_publication_channels', 'metrics'));
    }

    /** @param PermissionsEnum[] $allowed */
    private function user(array $allowed): User
    {
        $labels = array_map(fn (PermissionsEnum $permission) => $permission->label(), $allowed);

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

    private function writer(): User
    {
        return $this->user([PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_PUBLICATION_CREATE, PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH]);
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

    public function test_a_direct_publication_is_saved_without_post_and_without_address(): void
    {
        [$publication, $created] = $this->service()->createDirect($this->writer(), [
            'title' => ' Thông báo <b>nghỉ lễ</b> ', 'message' => 'Nội dung', 'image_url' => '', 'channels' => ['facebook'],
            'intent' => 'draft', 'idempotency_key' => self::KEY,
        ]);

        $this->assertTrue($created);
        $this->assertNull($publication->post_id);
        $this->assertSame('', $publication->public_url);
        $this->assertSame(self::PROJECT, $publication->project_key);
        $this->assertSame(app(LocaleManager::class)->default(), $publication->locale);
        $this->assertSame('Thông báo nghỉ lễ', $publication->title);
        $this->assertNull($publication->image_url);
        $this->assertSame(SocialPublicationStatus::DRAFT, $publication->status);
        $this->assertSame(['facebook'], $publication->channels->map(fn ($delivery) => $delivery->channel->value)->all());
    }

    public function test_the_typed_address_is_kept_and_a_bad_one_is_dropped(): void
    {
        [$good] = $this->service()->createDirect($this->writer(), ['title' => 'A', 'public_url' => 'https://example.test/x', 'intent' => 'draft', 'idempotency_key' => self::KEY]);
        [$bad] = $this->service()->createDirect($this->writer(), ['title' => 'B', 'public_url' => 'javascript:alert(1)', 'intent' => 'draft', 'idempotency_key' => 'aaaaaaaa-bbbb-cccc-dddd-000000000002']);

        $this->assertSame('https://example.test/x', $good->public_url);
        $this->assertSame('', $bad->public_url);
    }

    public function test_the_same_form_submitted_twice_creates_one_publication(): void
    {
        $input = ['title' => 'A', 'message' => 'B', 'intent' => 'draft', 'idempotency_key' => self::KEY];

        [$first, $createdFirst] = $this->service()->createDirect($this->writer(), $input);
        [$second, $createdSecond] = $this->service()->createDirect($this->writer(), $input);

        $this->assertTrue($createdFirst);
        $this->assertFalse($createdSecond);
        $this->assertSame($first->id, $second->id);
    }

    public function test_ready_needs_a_channel_and_a_text(): void
    {
        $refused = 0;
        foreach ([
            ['title' => 'A', 'message' => 'B', 'intent' => 'ready', 'idempotency_key' => self::KEY],
            ['title' => 'A', 'message' => '', 'channels' => ['facebook'], 'intent' => 'ready', 'idempotency_key' => 'aaaaaaaa-bbbb-cccc-dddd-000000000003'],
        ] as $input) {
            try {
                $this->service()->createDirect($this->writer(), $input);
            } catch (SocialException) {
                $refused++;
            }
        }

        $this->assertSame(2, $refused);
        $this->assertSame(0, SocialPublication::query()->count());
    }

    public function test_it_needs_the_create_permission(): void
    {
        $this->expectException(SocialException::class);

        $this->service()->createDirect($this->user([PermissionsEnum::SOCIAL_PUBLICATION_VIEW]), ['title' => 'A', 'intent' => 'draft', 'idempotency_key' => self::KEY]);
    }

    public function test_the_address_of_a_direct_publication_can_be_edited_while_it_is_a_draft(): void
    {
        [$publication] = $this->service()->createDirect($this->writer(), ['title' => 'A', 'message' => 'B', 'intent' => 'draft', 'idempotency_key' => self::KEY]);

        $this->service()->updateDraft($this->writer(), $publication, ['title' => 'A2', 'public_url' => 'https://example.test/y', 'intent' => 'draft']);

        $this->assertSame('https://example.test/y', $publication->fresh()->public_url);
        $this->assertSame('A2', $publication->fresh()->title);
    }

    public function test_a_direct_post_is_sent_to_facebook_as_plain_text_without_a_link(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_777'])]);

        [$publication] = $this->service()->createDirect($this->writer(), [
            'title' => 'Thông báo', 'message' => 'Nghỉ lễ 2/9', 'channels' => ['facebook'], 'intent' => 'ready', 'idempotency_key' => self::KEY,
        ]);
        $this->service()->publish($this->writer(), $publication);

        $delivery = $publication->channels()->first();
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status);
        $this->assertSame('9001_777', $delivery->remote_post_id);
        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/9001/feed')
                && ($data['message'] ?? null) === "Thông báo\n\nNghỉ lễ 2/9"
                && ! array_key_exists('link', $data);
        });
    }
}
