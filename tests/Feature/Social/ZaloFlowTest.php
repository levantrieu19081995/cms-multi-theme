<?php

declare(strict_types=1);

namespace Tests\Feature\Social;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Services\Social\Zalo\ZaloConnectionService;
use Tests\TestCase;

/**
 * Zalo OA end to end against a FAKED Zalo and SQLite :memory: only: connect (OAuth state + PKCE), token renewal (single-use refresh
 * token), publishing an article (including the "still processing" case), synchronization and removal.
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Social
 */
final class ZaloFlowTest extends TestCase
{
    private const PROJECT = 'nam-binh-duong';

    private const OA = '4462152339089565647';

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }

        config([
            'activitylog.enabled' => false,
            'queue.default' => 'sync',
            'cache.default' => 'array',
            'cmsbackend.projects.key' => self::PROJECT,
            'cmsbackend.social.zalo' => [
                'app_id' => '1234567890123', 'app_secret' => 'zalo-app-secret',
                'redirect_uri' => 'https://example.test/cms/truyen-thong/tai-khoan/zalo/callback',
                'authorize_url' => 'https://oauth.zaloapp.com/v4/oa/permission', 'token_url' => 'https://oauth.zaloapp.com/v4/oa/access_token',
                'api_base' => 'https://openapi.zalo.me/v2.0/', 'refresh_token_months' => 3, 'access_token_margin' => 300,
                'verify_attempts' => 2, 'verify_delay_ms' => 0, 'timeout' => 5, 'connect_timeout' => 2, 'state_ttl' => 600,
            ],
        ]);
        Cache::flush();
        foreach ([
            '2026_10_30_000001_create_social_accounts_table.php',
            '2026_10_30_000002_create_social_publications_table.php',
            '2026_10_30_000003_create_social_publication_channels_table.php',
            '2026_11_11_000001_add_metrics_to_social_publication_channels_table.php',
        ] as $file) {
            (require base_path('database/migrations/'.$file))->up();
        }
    }

    private function user(array $allowed = []): User
    {
        $labels = array_map(fn (PermissionsEnum $permission) => $permission->label(), $allowed ?: [
            PermissionsEnum::SOCIAL_ACCOUNT_MANAGE, PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH,
            PermissionsEnum::SOCIAL_PUBLICATION_RETRY, PermissionsEnum::SOCIAL_PUBLICATION_REMOVE, PermissionsEnum::SOCIAL_STATISTICS_VIEW,
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

    private function newSession(): Store
    {
        return new Store('test', new ArraySessionHandler(120));
    }

    private function connection(): ZaloConnectionService
    {
        return app(ZaloConnectionService::class);
    }

    private function service(): SocialPublicationService
    {
        return app(SocialPublicationService::class);
    }

    /** A connected Zalo account whose access token is still valid unless told otherwise. */
    private function connectZalo(int $accessExpiresIn = 3600): SocialAccount
    {
        $account = new SocialAccount([
            'project_key' => self::PROJECT, 'platform' => SocialChannel::ZALO->value, 'name' => 'OA NBD', 'external_account_id' => self::OA,
            'status' => SocialAccountStatus::CONNECTED->value, 'token_expires_at' => now()->addMonths(3),
        ]);
        $account->credentials = ['access_token' => 'AT-1', 'refresh_token' => 'RT-1', 'access_expires_at' => time() + $accessExpiresIn];
        $account->save();

        return $account;
    }

    private function readyPublication(): SocialPublication
    {
        $publication = SocialPublication::query()->create([
            'post_id' => null, 'project_key' => self::PROJECT, 'locale' => 'vi', 'title' => 'Tiêu đề', 'message' => 'Nội dung',
            'public_url' => 'https://example.test/bai.htm', 'status' => SocialPublicationStatus::READY->value, 'created_by' => 7,
        ]);
        $publication->channels()->create(['channel' => 'zalo', 'status' => SocialChannelStatus::DRAFT->value]);

        return $publication->fresh();
    }

    private function delivery(SocialPublication $publication)
    {
        return $publication->channels()->where('channel', 'zalo')->first();
    }

    private function oaAnswer(string $name = 'OA NBD'): array
    {
        return ['error' => 0, 'message' => 'Success', 'data' => ['oaid' => self::OA, 'name' => $name, 'is_verified' => true, 'num_follower' => 10, 'avatar' => 'https://s.zalo/a.jpg', 'package_name' => 'Nâng cao']];
    }

    // ------------------------------------------------------------------ connect

    public function test_connect_runs_the_oauth_flow_with_pkce_and_saves_the_account(): void
    {
        Http::fake([
            'oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT-NEW', 'refresh_token' => 'RT-NEW', 'expires_in' => '90000']),
            'openapi.zalo.me/v2.0/oa/getoa' => Http::response($this->oaAnswer()),
        ]);
        $session = $this->newSession();
        $user = $this->user();

        $url = $this->connection()->start($session, $user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('1234567890123', $query['app_id']);
        $this->assertSame(43, strlen($query['code_challenge']), 'SHA-256 in Base64URL without padding is 43 characters.');

        $account = $this->connection()->handleCallback($session, $user, ['code' => 'CODE1', 'oa_id' => self::OA, 'state' => $query['state']]);

        // The code_verifier sent to Zalo is the one whose SHA-256 was announced as the challenge.
        $exchange = Http::recorded()->first()[0];
        $this->assertSame($query['code_challenge'], rtrim(strtr(base64_encode(hash('sha256', $exchange->data()['code_verifier'], true)), '+/', '-_'), '='));
        $this->assertSame('zalo-app-secret', $exchange->header('secret_key')[0] ?? null);

        $this->assertSame(self::OA, $account->external_account_id);
        $this->assertSame(SocialAccountStatus::CONNECTED, $account->status);
        $this->assertSame('AT-NEW', $account->credentials['access_token']);
        $this->assertSame('RT-NEW', $account->credentials['refresh_token']);
        // The connection lives as long as the refresh token (3 months), not as long as the access token (25 hours).
        $this->assertTrue($account->token_expires_at->greaterThan(now()->addMonths(2)));
        $this->assertTrue($account->isConnected());
        $this->assertSame(7, (int) $account->connected_by);
    }

    public function test_a_callback_state_works_once_only_and_a_wrong_one_is_refused(): void
    {
        Http::fake([
            'oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000']),
            'openapi.zalo.me/*' => Http::response($this->oaAnswer()),
        ]);
        $session = $this->newSession();
        $user = $this->user();
        parse_str((string) parse_url($this->connection()->start($session, $user), PHP_URL_QUERY), $query);

        try {
            $this->connection()->handleCallback($session, $user, ['code' => 'C', 'state' => 'forged-state']);
            $this->fail('A forged state must be refused.');
        } catch (SocialException $e) {
            $this->assertSame('state_invalid', $e->errorCode);
        }
        // The real state was burnt by the failed check above (the session value is consumed on every callback).
        $this->expectException(SocialException::class);
        $this->connection()->handleCallback($session, $user, ['code' => 'C', 'state' => $query['state']]);
    }

    public function test_a_callback_naming_another_oa_than_the_token_is_refused(): void
    {
        Http::fake([
            'oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000']),
            'openapi.zalo.me/*' => Http::response($this->oaAnswer()),
        ]);
        $session = $this->newSession();
        $user = $this->user();
        parse_str((string) parse_url($this->connection()->start($session, $user), PHP_URL_QUERY), $query);

        try {
            $this->connection()->handleCallback($session, $user, ['code' => 'C', 'oa_id' => '999', 'state' => $query['state']]);
            $this->fail('An OA id that differs from the token must be refused.');
        } catch (SocialException $e) {
            $this->assertSame('oa_mismatch', $e->errorCode);
        }
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_connect_is_refused_when_an_oa_is_already_connected_and_without_permission(): void
    {
        $this->connectZalo();

        try {
            $this->connection()->start($this->newSession(), $this->user());
            $this->fail('A second connection must be refused.');
        } catch (SocialException $e) {
            $this->assertSame('already_connected', $e->errorCode);
        }
        $this->expectException(SocialException::class);
        $this->connection()->start($this->newSession(), $this->user([PermissionsEnum::SOCIAL_PUBLICATION_VIEW]));
    }

    public function test_check_renews_an_expired_access_token_and_stores_both_new_tokens(): void
    {
        $account = $this->connectZalo(accessExpiresIn: -10);
        Http::fake([
            'oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT-2', 'refresh_token' => 'RT-2', 'expires_in' => '90000']),
            'openapi.zalo.me/v2.0/oa/getoa' => Http::response($this->oaAnswer('OA NBD moi')),
        ]);

        [$checked, $result] = $this->connection()->check($this->user());

        $this->assertTrue($result->success);
        $stored = $account->fresh();
        $this->assertSame('AT-2', $stored->credentials['access_token']);
        $this->assertSame('RT-2', $stored->credentials['refresh_token'], 'The single-use refresh token must be replaced by the new one.');
        $this->assertSame('OA NBD moi', $stored->name);
        $this->assertSame(10, $stored->credentials['followers']);
        $refresh = Http::recorded()->first()[0];
        $this->assertSame('refresh_token', $refresh->data()['grant_type']);
        $this->assertSame('RT-1', $refresh->data()['refresh_token']);
        $getoa = Http::recorded()->last()[0];
        $this->assertSame('AT-2', $getoa->header('access_token')[0] ?? null);
    }

    public function test_a_refused_refresh_token_marks_the_account_expired(): void
    {
        $account = $this->connectZalo(accessExpiresIn: -10);
        Http::fake(['oauth.zaloapp.com/*' => Http::response(['error' => -118, 'error_name' => 'invalid_grant'], 400)]);

        [, $result] = $this->connection()->check($this->user());

        $this->assertFalse($result->success);
        $this->assertSame('auth_expired', $result->errorCode);
        $this->assertSame(SocialAccountStatus::EXPIRED, $account->fresh()->status);
    }

    public function test_disconnect_erases_the_tokens_but_keeps_the_oa(): void
    {
        $account = $this->connectZalo();

        $this->connection()->disconnect($this->user());

        $stored = $account->fresh();
        $this->assertSame(SocialAccountStatus::DISCONNECTED, $stored->status);
        $this->assertEmpty($stored->credentials);
        $this->assertSame(self::OA, $stored->external_account_id);
    }

    // ------------------------------------------------------------------ publish

    public function test_publish_sends_an_article_and_marks_the_delivery_published(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);

        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status);
        $this->assertSame('ARTICLE-9', $delivery->remote_post_id);
        $this->assertSame(SocialPublicationStatus::PUBLISHED, $publication->fresh()->status);
    }

    public function test_an_article_zalo_is_still_processing_stays_processing_and_a_sync_publishes_it_without_a_second_article(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::sequence()
                ->push(['error' => -214])->push(['error' => -214])   // right after creation (2 attempts)
                ->push(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]), // the later synchronization
            'openapi.zalo.me/v2.0/article/getdetail*' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);

        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::PROCESSING, $delivery->status, 'Not a failure: the article may already exist.');
        $this->assertSame('pending:PROGRESS-1', $delivery->remote_post_id);
        $this->assertSame(SocialPublicationStatus::PROCESSING, $publication->fresh()->status);

        // A person cannot send it again while it is processing.
        try {
            $this->service()->retry($this->user(), $publication->fresh());
            $this->fail('A processing delivery must not be retried.');
        } catch (SocialException $e) {
            $this->assertSame('error_nothing_to_retry', $e->errorCode);
        }

        $result = $this->service()->sync($this->user(), $publication->fresh());

        $this->assertSame(1, $result['synced']);
        $delivery = $this->delivery($publication);
        $this->assertSame(SocialChannelStatus::PUBLISHED, $delivery->status);
        $this->assertSame('ARTICLE-9', $delivery->remote_post_id);
        $this->assertNotNull($delivery->published_at);
        $this->assertSame(SocialPublicationStatus::PUBLISHED, $publication->fresh()->status);
        $creations = Http::recorded()->filter(fn (array $pair) => str_ends_with($pair[0]->url(), '/article/create'))->count();
        $this->assertSame(1, $creations, 'Exactly one article is created.');
    }

    public function test_sync_of_an_article_still_processing_reports_it_and_changes_nothing(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => -214]),
        ]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $result = $this->service()->sync($this->user(), $publication->fresh());

        $this->assertSame(0, $result['synced']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(SocialChannelStatus::PROCESSING, $this->delivery($publication)->status);
    }

    public function test_a_token_zalo_refuses_is_renewed_once_and_the_call_repeated(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::sequence()
                ->push(['error' => -216, 'message' => 'access_token is invalid'])
                ->push(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT-2', 'refresh_token' => 'RT-2', 'expires_in' => '90000']),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);

        $this->assertSame(SocialChannelStatus::PUBLISHED, $this->delivery($publication)->status);
        $creates = Http::recorded()->filter(fn (array $pair) => str_ends_with($pair[0]->url(), '/article/create'))->map(fn (array $pair) => $pair[0]);
        $this->assertSame(['AT-1', 'AT-2'], $creates->map(fn (Request $request) => $request->header('access_token')[0] ?? null)->values()->all());
        $this->assertSame('RT-2', SocialAccount::query()->first()->credentials['refresh_token']);
    }

    public function test_a_refused_creation_fails_the_delivery_and_a_person_can_retry(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::sequence()
                ->push(['error' => -223, 'message' => 'no permission'])
                ->push(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);
        $publication = $this->readyPublication();

        $this->service()->publish($this->user(), $publication);
        $this->assertSame(SocialChannelStatus::FAILED, $this->delivery($publication)->status);
        $this->assertSame('permission', $this->delivery($publication)->error_code);

        $this->service()->retry($this->user(), $publication->fresh());
        $this->assertSame(SocialChannelStatus::PUBLISHED, $this->delivery($publication)->status);
        $this->assertSame(2, $this->delivery($publication)->attempts);
    }

    public function test_remove_deletes_the_article(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
            'openapi.zalo.me/v2.0/article/remove' => Http::response(['error' => 0, 'message' => 'Success']),
        ]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);

        $result = $this->service()->remove($this->user(), $publication->fresh());

        $this->assertSame(1, $result['removed']);
        $this->assertSame(SocialChannelStatus::REMOVED, $this->delivery($publication)->status);
        $remove = Http::recorded()->last()[0];
        $this->assertSame(['id' => 'ARTICLE-9'], $remove->data());
    }

    public function test_sync_stores_views_shares_and_the_address_of_the_article(): void
    {
        $this->connectZalo();
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
            'openapi.zalo.me/v2.0/article/getdetail*' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
            'openapi.zalo.me/v2.0/article/getslice*' => Http::response(['error' => 0, 'data' => ['total' => 1, 'medias' => [
                ['id' => 'ARTICLE-9', 'status' => 'show', 'total_view' => 41, 'total_share' => 6, 'link_view' => 'https://zalo.me/a/ARTICLE-9'],
            ]]]),
        ]);
        $publication = $this->readyPublication();
        $this->service()->publish($this->user(), $publication);
        $this->assertNull($this->delivery($publication)->remote_url, 'The address is only known after a synchronization.');

        $result = $this->service()->sync($this->user(), $publication->fresh());

        $this->assertSame(1, $result['synced']);
        $delivery = $this->delivery($publication);
        $this->assertSame(['views' => 41, 'shares' => 6], $delivery->metrics);
        $this->assertSame('https://zalo.me/a/ARTICLE-9', $delivery->remote_url);
        $this->assertNotNull($delivery->last_synced_at);
    }
}
