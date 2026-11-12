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
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Tests\TestCase;

/**
 * Publish -> job -> adapter -> delivery status, against a FAKED Graph API and SQLite :memory: only (never the real Facebook,
 * never the development database).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Social
 */
final class SocialPublishFlowTest extends TestCase
{
    private const PROJECT = 'nam-binh-duong';

    private const TOKEN = 'PAGE-TOKEN-SECRET-123';

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

    private function user(array $allowed = []): User
    {
        $labels = array_map(fn (PermissionsEnum $permission) => $permission->label(), $allowed ?: [
            PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH, PermissionsEnum::SOCIAL_PUBLICATION_RETRY,
            PermissionsEnum::SOCIAL_PUBLICATION_REMOVE, PermissionsEnum::SOCIAL_STATISTICS_VIEW,
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

    private function connectFacebook(string $project = self::PROJECT): SocialAccount
    {
        $account = new SocialAccount([
            'project_key' => $project, 'platform' => SocialChannel::FACEBOOK->value, 'name' => 'Page NBD',
            'external_account_id' => '9001', 'status' => SocialAccountStatus::CONNECTED->value,
        ]);
        $account->credentials = ['page_access_token' => self::TOKEN];
        $account->save();

        return $account;
    }

    /** A ready publication with a Facebook delivery (and, when asked, a Zalo one). */
    private function readyPublication(bool $withZalo = false): SocialPublication
    {
        $publication = SocialPublication::query()->create([
            'post_id' => null, 'project_key' => self::PROJECT, 'locale' => 'vi', 'title' => 'Tiêu đề',
            'message' => 'Nội dung', 'public_url' => 'https://example.test/bai.htm',
            'status' => SocialPublicationStatus::READY->value, 'created_by' => 7,
        ]);
        $channels = $withZalo ? [SocialChannel::FACEBOOK, SocialChannel::ZALO] : [SocialChannel::FACEBOOK];
        foreach ($channels as $channel) {
            $publication->channels()->create(['channel' => $channel->value, 'status' => SocialChannelStatus::DRAFT->value]);
        }

        return $publication->fresh();
    }

    private function service(): SocialPublicationService
    {
        return app(SocialPublicationService::class);
    }

    private function delivery(SocialPublication $publication)
    {
        return $publication->channels()->where('channel', 'facebook')->first();
    }

    public function test_publish_sends_the_post_and_marks_the_delivery_published(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);

        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status);
        $this->assertSame('9001_555', $delivery->remote_post_id);
        $this->assertSame('https://www.facebook.com/9001_555', $delivery->remote_url);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->published_at);
        $this->assertNull($delivery->error_message);
        $this->assertNotNull($delivery->social_account_id);
        $fresh = $publication->fresh();
        $this->assertSame(SocialPublicationStatus::PUBLISHED, $fresh->status);
        $this->assertNotNull($fresh->published_at);
        Http::assertSentCount(1);
    }

    public function test_publish_is_refused_without_the_publish_permission_and_sends_nothing(): void
    {
        $this->connectFacebook();
        Http::fake();
        $publication = $this->readyPublication();

        try {
            $this->service()->publish($this->user([PermissionsEnum::SOCIAL_PUBLICATION_VIEW]), $publication);
            $this->fail('A user without the publish permission must be refused.');
        } catch (SocialException $e) {
            $this->assertSame('forbidden', $e->errorCode);
        }

        Http::assertNothingSent();
        $this->assertSame(SocialChannelStatus::DRAFT, $this->delivery($publication)->status);
    }

    public function test_publish_is_refused_when_a_chosen_channel_is_not_connected_and_queues_nothing(): void
    {
        $this->connectFacebook();
        Http::fake();
        $publication = $this->readyPublication(withZalo: true); // Zalo has no adapter and no account

        $this->expectException(SocialException::class);
        try {
            $this->service()->publish($this->user(), $publication);
        } finally {
            Http::assertNothingSent();
            $this->assertSame(SocialChannelStatus::DRAFT, $this->delivery($publication)->status);
        }
    }

    public function test_a_failed_post_is_marked_failed_with_a_safe_message_and_can_be_retried_once_by_a_person(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'bad '.self::TOKEN, 'code' => 190]], 400)
            ->push(['id' => '9001_777'])]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);

        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::FAILED, $delivery->status);
        $this->assertSame('auth_expired', $delivery->error_code);
        $this->assertStringNotContainsString(self::TOKEN, (string) $delivery->error_message);
        $this->assertSame(SocialPublicationStatus::FAILED, $publication->fresh()->status);

        // Nothing retries by itself: only the explicit "Đăng lại" sends again.
        Http::assertSentCount(1);
        $this->service()->retry($this->user(), $publication->fresh());

        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status);
        $this->assertSame('9001_777', $delivery->remote_post_id);
        $this->assertSame(2, $delivery->attempts);
        $this->assertNull($delivery->error_code);
        Http::assertSentCount(2);
    }

    public function test_retry_with_nothing_failed_is_refused(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $this->expectException(SocialException::class);
        $this->service()->retry($this->user(), $publication->fresh());
    }

    public function test_a_delivery_is_sent_only_once_even_if_its_job_runs_twice(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $this->service()->deliver($this->delivery($publication)->id);
        $this->service()->deliver($this->delivery($publication)->id);

        Http::assertSentCount(1);
        $this->assertSame(1, $this->delivery($publication)->attempts);
    }

    public function test_sync_stores_the_metrics_and_remove_takes_the_post_down(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => function (Request $request) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => '9001_555']);
            }
            if ($request->method() === 'DELETE') {
                return Http::response(['success' => true]);
            }
            if (str_contains(urldecode($request->url()), 'reactions.summary')) {
                return Http::response(['id' => '9001_555', 'reactions' => ['summary' => ['total_count' => 5]], 'comments' => ['summary' => ['total_count' => 2]], 'shares' => ['count' => 1]]);
            }

            return Http::response(['id' => '9001_555', 'permalink_url' => 'https://www.facebook.com/9001/posts/555']);
        }]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $synced = $this->service()->sync($this->user(), $publication->fresh());
        $delivery = $this->delivery($publication);
        $this->assertSame(1, $synced['synced']);
        $this->assertSame([], $synced['errors']);
        $this->assertSame(['reactions' => 5, 'comments' => 2, 'shares' => 1], $delivery->metrics);
        $this->assertSame('https://www.facebook.com/9001/posts/555', $delivery->remote_url);
        $this->assertNotNull($delivery->last_synced_at);

        $removed = $this->service()->remove($this->user(), $publication->fresh());
        $this->assertSame(1, $removed['removed']);
        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::REMOVED, $delivery->status);
        $this->assertNotNull($delivery->removed_at);
    }

    public function test_remove_refused_by_facebook_keeps_the_delivery_published_and_reports_why(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => function (Request $request) {
            return $request->method() === 'DELETE'
                ? Http::response(['error' => ['message' => 'forbidden', 'code' => 200]], 403)
                : Http::response(['id' => '9001_555']);
        }]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $result = $this->service()->remove($this->user(), $publication->fresh());

        $this->assertSame(0, $result['removed']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString(__('admin.social.fb_remove_not_allowed'), $result['errors'][0]);
        $this->assertSame(SocialChannelStatus::PUBLISHED, $this->delivery($publication)->status);
    }

    public function test_remove_and_sync_need_their_own_permissions(): void
    {
        $this->connectFacebook();
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);
        $viewOnly = $this->user([PermissionsEnum::SOCIAL_PUBLICATION_VIEW]);

        foreach (['remove', 'sync', 'retry'] as $method) {
            try {
                $this->service()->{$method}($viewOnly, $publication->fresh());
                $this->fail($method.' must be refused without its permission.');
            } catch (SocialException $e) {
                $this->assertSame('forbidden', $e->errorCode, $method);
            }
        }
    }

    public function test_an_account_of_another_project_is_never_used(): void
    {
        $this->connectFacebook('ban-de-an'); // connected, but for another project
        Http::fake();
        $publication = $this->readyPublication();

        $this->expectException(SocialException::class);
        try {
            $this->service()->publish($this->user(), $publication);
        } finally {
            Http::assertNothingSent();
        }
    }
}
