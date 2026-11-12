<?php

declare(strict_types=1);

namespace Tests\Unit\Social;

use Illuminate\Support\Facades\Http;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Services\Social\Zalo\ZaloChannelAdapter;
use Tests\TestCase;

/**
 * The Zalo adapter against a faked Zalo: what it sends as an article, how it treats an article that is still being processed
 * (never as a failure), and that a token never reaches a result. No database: the account holds a still-valid access token, so no
 * refresh (and no DB access) takes place here; refresh and persistence are covered by the SQLite feature test.
 */
final class ZaloAdapterTest extends TestCase
{
    private const TOKEN = 'OA-ACCESS-TOKEN-SECRET';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cmsbackend.social.zalo' => [
            'app_id' => '1234567890123', 'app_secret' => 'zalo-app-secret', 'redirect_uri' => 'https://example.test/cb',
            'authorize_url' => 'https://oauth.zaloapp.com/v4/oa/permission', 'token_url' => 'https://oauth.zaloapp.com/v4/oa/access_token',
            'api_base' => 'https://openapi.zalo.me/v2.0/', 'refresh_token_months' => 3, 'access_token_margin' => 300,
            'verify_attempts' => 3, 'verify_delay_ms' => 0, 'timeout' => 5, 'connect_timeout' => 2, 'state_ttl' => 600,
        ]]);
    }

    private function adapter(): ZaloChannelAdapter
    {
        return app(ZaloChannelAdapter::class);
    }

    private function account(): SocialAccount
    {
        $account = new SocialAccount(['external_account_id' => '4462152339089565647', 'name' => 'OA Nam Bình Dương']);
        $account->credentials = ['access_token' => self::TOKEN, 'refresh_token' => 'RT', 'access_expires_at' => time() + 3600];

        return $account;
    }

    private function delivery(?string $remoteId = null, array $publication = []): SocialPublicationChannel
    {
        $delivery = new SocialPublicationChannel(['remote_post_id' => $remoteId]);
        $delivery->setRelation('publication', new SocialPublication($publication + [
            'title' => 'Tiêu đề bài', 'message' => 'Nội dung ngắn', 'public_url' => 'https://example.test/bai-viet.htm', 'image_url' => 'https://example.test/anh.jpg',
        ]));

        return $delivery;
    }

    private function sent(): array
    {
        return Http::recorded()->map(fn (array $pair) => $pair[0])->values()->all();
    }

    public function test_publish_creates_an_article_then_reads_its_id(): void
    {
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame('ARTICLE-9', $result->remoteId);
        [$create, $verify] = $this->sent();
        $this->assertSame(self::TOKEN, $create->header('access_token')[0] ?? null);
        $this->assertSame(['token' => 'PROGRESS-1'], $verify->data());
        $article = $create->data();
        $this->assertSame('normal', $article['type']);
        $this->assertSame('Tiêu đề bài', $article['title']);
        $this->assertSame('OA Nam Bình Dương', $article['author']);
        $this->assertSame('Nội dung ngắn', $article['description']);
        $this->assertSame('show', $article['status']);
        $this->assertSame(['cover_type' => 'photo', 'photo_url' => 'https://example.test/anh.jpg', 'status' => 'show'], $article['cover']);
        $this->assertSame('text', $article['body'][0]['type']);
        $this->assertSame('Nội dung ngắn', $article['body'][0]['content']);
        $this->assertStringContainsString('https://example.test/bai-viet.htm', $article['body'][1]['content']);
    }

    public function test_article_respects_the_documented_limits_and_skips_a_cover_that_is_not_https(): void
    {
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'P']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => 0, 'data' => ['id' => 'A']]),
        ]);
        $account = $this->account();
        $account->name = str_repeat('Đ', 80);

        $this->adapter()->publish($this->delivery(null, ['title' => str_repeat('T', 200), 'message' => str_repeat('ê', 500), 'image_url' => 'http://example.test/a.jpg']), $account);

        $article = $this->sent()[0]->data();
        $this->assertSame(150, mb_strlen($article['title']));
        $this->assertSame(50, mb_strlen($article['author']));
        $this->assertSame(300, mb_strlen($article['description']));
        $this->assertArrayNotHasKey('cover', $article);
    }

    public function test_an_article_still_processing_is_pending_never_a_failure(): void
    {
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response(['error' => -214, 'message' => 'processing']),
        ]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertFalse($result->success);
        $this->assertTrue($result->pending);
        $this->assertSame('pending:PROGRESS-1', $result->remoteId);
        // One create, then the configured number of verify attempts.
        $this->assertCount(1 + 3, $this->sent());
    }

    public function test_a_verify_error_after_creation_is_pending_too_because_the_article_may_exist(): void
    {
        Http::fake([
            'openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::response('gateway down', 502),
        ]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertTrue($result->pending);
        $this->assertSame('pending:PROGRESS-1', $result->remoteId);
    }

    public function test_a_refused_creation_is_a_failure_with_a_safe_message(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/create' => Http::response(['error' => -223, 'message' => 'denied '.self::TOKEN])]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertFalse($result->success);
        $this->assertFalse($result->pending);
        $this->assertSame('permission', $result->errorCode);
        $this->assertStringNotContainsString(self::TOKEN, (string) $result->message);
        $this->assertCount(1, $this->sent());
    }

    public function test_remove_deletes_a_known_article_and_refuses_a_pending_one_without_calling_zalo(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => 0, 'message' => 'Success'])]);

        $pending = $this->adapter()->remove($this->delivery('pending:PROGRESS-1'), $this->account());
        $this->assertFalse($pending->success);
        $this->assertSame('processing', $pending->errorCode);
        Http::assertNothingSent();

        $done = $this->adapter()->remove($this->delivery('ARTICLE-9'), $this->account());
        $this->assertTrue($done->success);
        $request = $this->sent()[0];
        $this->assertSame('https://openapi.zalo.me/v2.0/article/remove', $request->url());
        $this->assertSame(['id' => 'ARTICLE-9'], $request->data());
    }

    public function test_sync_status_resolves_a_pending_article_and_checks_a_known_one(): void
    {
        Http::fake([
            'openapi.zalo.me/v2.0/article/verify' => Http::sequence()
                ->push(['error' => -214])
                ->push(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
            'openapi.zalo.me/v2.0/article/getdetail*' => Http::response(['error' => 0, 'data' => ['id' => 'ARTICLE-9']]),
        ]);

        $still = $this->adapter()->syncStatus($this->delivery('pending:PROGRESS-1'), $this->account());
        $this->assertTrue($still->pending);
        $this->assertSame('pending:PROGRESS-1', $still->remoteId);

        $resolved = $this->adapter()->syncStatus($this->delivery('pending:PROGRESS-1'), $this->account());
        $this->assertTrue($resolved->success);
        $this->assertSame('ARTICLE-9', $resolved->remoteId);

        $known = $this->adapter()->syncStatus($this->delivery('ARTICLE-9'), $this->account());
        $this->assertTrue($known->success);
        $this->assertStringContainsString('article/getdetail?id=ARTICLE-9', $this->sent()[2]->url());
    }

    public function test_validate_connection_returns_the_oa_profile(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/oa/getoa' => Http::response(['error' => 0, 'data' => ['oaid' => '4462152339089565647', 'name' => 'OA', 'is_verified' => true, 'num_follower' => 7]])]);

        $result = $this->adapter()->validateConnection($this->account());

        $this->assertTrue($result->success);
        $this->assertSame('4462152339089565647', $result->remoteId);
        $this->assertSame(7, $result->data['followers']);
        $this->assertTrue($result->data['is_verified']);
    }

    /** A page of the article list as Zalo answers it. */
    private function listPage(array $ids, int $total, array $extra = []): array
    {
        return ['error' => 0, 'message' => 'Success', 'data' => ['total' => $total, 'medias' => array_map(
            fn (string $id) => $extra + ['id' => $id, 'title' => 'T', 'status' => 'show', 'total_view' => 12, 'total_share' => 3, 'link_view' => 'https://zalo.me/a/'.$id],
            $ids,
        )]];
    }

    public function test_sync_metrics_reads_views_shares_and_the_address_from_the_article_list(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/getslice*' => Http::response($this->listPage(['OTHER', 'ARTICLE-9'], 2))]);

        $result = $this->adapter()->syncMetrics($this->delivery('ARTICLE-9'), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame(['views' => 12, 'shares' => 3], $result->data);
        $this->assertSame('https://zalo.me/a/ARTICLE-9', $result->remoteUrl);
        $request = $this->sent()[0];
        $this->assertSame('GET', $request->method());
        $this->assertSame(self::TOKEN, $request->header('access_token')[0] ?? null);
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $this->assertSame(['offset' => '0', 'limit' => '10', 'type' => 'normal'], $query);
    }

    public function test_sync_metrics_reads_the_next_page_when_the_article_is_not_on_the_first(): void
    {
        $first = array_map(fn (int $n) => 'X'.$n, range(1, 10));
        Http::fake(['openapi.zalo.me/v2.0/article/getslice*' => Http::sequence()
            ->push($this->listPage($first, 14))
            ->push($this->listPage(['Y1', 'ARTICLE-9'], 14))]);

        $result = $this->adapter()->syncMetrics($this->delivery('ARTICLE-9'), $this->account());

        $this->assertSame(['views' => 12, 'shares' => 3], $result->data);
        $this->assertCount(2, $this->sent());
        parse_str((string) parse_url($this->sent()[1]->url(), PHP_URL_QUERY), $query);
        $this->assertSame('10', $query['offset']);
    }

    public function test_sync_metrics_for_an_article_not_in_the_list_is_empty_not_an_error(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/getslice*' => Http::response($this->listPage(['A', 'B'], 2))]);

        $result = $this->adapter()->syncMetrics($this->delivery('ARTICLE-9'), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame([], $result->data);
        $this->assertNull($result->remoteUrl);
        $this->assertCount(1, $this->sent(), 'The last page was reached: no further request.');
    }

    public function test_sync_metrics_ignores_a_link_that_is_not_https_and_skips_a_pending_article(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/getslice*' => Http::response($this->listPage(['ARTICLE-9'], 1, ['link_view' => 'javascript:alert(1)']))]);

        $unsafe = $this->adapter()->syncMetrics($this->delivery('ARTICLE-9'), $this->account());
        $this->assertNull($unsafe->remoteUrl);
        $this->assertSame(12, $unsafe->data['views']);

        $pending = $this->adapter()->syncMetrics($this->delivery('pending:PROGRESS-1'), $this->account());
        $this->assertSame([], $pending->data);
        $this->assertCount(1, $this->sent(), 'A pending article is not looked up.');
    }

    public function test_sync_metrics_failure_has_a_safe_message(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/getslice*' => Http::response(['error' => -32, 'message' => 'rate '.self::TOKEN])]);

        $result = $this->adapter()->syncMetrics($this->delivery('ARTICLE-9'), $this->account());

        $this->assertFalse($result->success);
        $this->assertSame('rate_limit', $result->errorCode);
        $this->assertStringNotContainsString(self::TOKEN, (string) $result->message);
    }
}
