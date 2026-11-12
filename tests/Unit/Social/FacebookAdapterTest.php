<?php

declare(strict_types=1);

namespace Tests\Unit\Social;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Services\Social\Facebook\FacebookChannelAdapter;
use Tests\TestCase;

/**
 * The Facebook adapter against a faked Graph API (never the real one): what it sends, how it reads the answers, and that a
 * token never reaches a result or a message. No database: models are only built in memory.
 */
final class FacebookAdapterTest extends TestCase
{
    private const TOKEN = 'PAGE-TOKEN-SECRET-123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cmsbackend.social.facebook' => [
            'app_id' => '123456789', 'app_secret' => 'app-secret-value', 'graph_version' => 'v27.0',
            'redirect_uri' => 'https://example.test/cms/truyen-thong/tai-khoan/facebook/callback',
            'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],
            'timeout' => 5, 'connect_timeout' => 2, 'state_ttl' => 600,
        ]]);
    }

    private function adapter(): FacebookChannelAdapter
    {
        return app(FacebookChannelAdapter::class);
    }

    private function account(?string $token = self::TOKEN): SocialAccount
    {
        $account = new SocialAccount(['external_account_id' => '9001', 'name' => 'Page']);
        $account->credentials = $token === null ? [] : ['page_access_token' => $token];

        return $account;
    }

    private function delivery(?string $remoteId = null, ?string $image = null, string $message = 'Nội dung ngắn'): SocialPublicationChannel
    {
        $delivery = new SocialPublicationChannel(['remote_post_id' => $remoteId]);
        $delivery->setRelation('publication', new SocialPublication([
            'title' => 'Tiêu đề bài', 'message' => $message, 'image_url' => $image, 'public_url' => 'https://example.test/bai-viet.htm',
        ]));

        return $delivery;
    }

    public function test_publish_without_a_picture_posts_a_link_post_on_the_page_feed(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame('9001_555', $result->remoteId);
        $this->assertSame('https://www.facebook.com/9001_555', $result->remoteUrl);
        $sent = Http::recorded();
        $this->assertCount(1, $sent);
        /** @var Request $request */
        $request = $sent->first()[0];
        $body = $request->data();

        $this->assertSame('POST', $request->method());
        $this->assertStringContainsString('graph.facebook.com/v27.0/9001/feed', $request->url());
        $this->assertSame('Bearer '.self::TOKEN, $request->header('Authorization')[0] ?? '');
        $this->assertStringNotContainsString(self::TOKEN, $request->url());
        $this->assertSame('https://example.test/bai-viet.htm', $body['link'] ?? null);
        // Title, blank line, excerpt; the card is built by Facebook from the link.
        $this->assertSame("Tiêu đề bài\n\nNội dung ngắn", $body['message'] ?? null);
    }

    /** A real 1x1 PNG: the picture the site serves in these tests. */
    private function png(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    /** @return array<string, mixed> the parts of a multipart request, by field name */
    private function parts(Request $request): array
    {
        return array_column($request->data(), 'contents', 'name');
    }

    /** @return array<int, Request> the requests that went to Graph (not the ones that downloaded the picture) */
    private function graphRequests(): array
    {
        return array_values(array_filter(
            array_map(fn (array $pair) => $pair[0], Http::recorded()->all()),
            fn (Request $request) => str_contains($request->url(), 'graph.facebook.com'),
        ));
    }

    public function test_publish_with_a_picture_uploads_the_file_as_one_photo_post_with_title_excerpt_and_address_as_caption(): void
    {
        Http::fake([
            'example.test/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
            'graph.facebook.com/*' => Http::response(['id' => '777', 'post_id' => '9001_555']),
        ]);

        $result = $this->adapter()->publish($this->delivery(null, 'https://example.test/media/avatar.jpg'), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame('9001_555', $result->remoteId, 'The post id (not the photo id) is kept: reactions, comments and delete work on it.');
        $this->assertSame('https://www.facebook.com/9001_555', $result->remoteUrl);
        $graph = $this->graphRequests();
        $this->assertCount(1, $graph);
        $request = $graph[0];

        $this->assertSame('POST', $request->method());
        $this->assertStringContainsString('graph.facebook.com/v27.0/9001/photos', $request->url());
        $this->assertStringNotContainsString('/feed', $request->url());
        $this->assertSame('Bearer '.self::TOKEN, $request->header('Authorization')[0] ?? '');
        $this->assertTrue($request->isMultipart(), 'The picture is uploaded as a file.');
        $data = $this->parts($request);
        $this->assertSame($this->png(), $data['source'] ?? null, 'Facebook receives the bytes, it is not asked to fetch a URL.');
        $this->assertArrayNotHasKey('url', $data);
        $this->assertArrayNotHasKey('link', $data);
        $this->assertSame("Tiêu đề bài\n\nNội dung ngắn\n\nhttps://example.test/bai-viet.htm", $data['message'] ?? null);
    }

    public function test_photo_post_falls_back_to_the_photo_id_when_meta_gives_no_post_id(): void
    {
        Http::fake([
            'example.test/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
            'graph.facebook.com/*' => Http::response(['id' => '777']),
        ]);

        $result = $this->adapter()->publish($this->delivery(null, 'https://example.test/a.png'), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame('777', $result->remoteId);
    }

    public function test_the_title_is_not_repeated_when_the_message_is_empty(): void
    {
        Http::fake([
            'example.test/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
            'graph.facebook.com/*' => Http::response(['post_id' => '9001_556']),
        ]);

        $this->adapter()->publish($this->delivery(null, 'https://example.test/a.png', ''), $this->account());

        $this->assertSame("Tiêu đề bài\n\nhttps://example.test/bai-viet.htm", $this->parts($this->graphRequests()[0])['message'] ?? null);
    }

    public function test_a_picture_that_is_not_https_is_not_downloaded_and_the_post_is_a_link_post(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_557'])]);

        $this->adapter()->publish($this->delivery(null, 'http://example.test/a.png'), $this->account());

        $this->assertCount(1, Http::recorded());
        $this->assertStringContainsString('/9001/feed', Http::recorded()->first()[0]->url());
    }

    public function test_a_picture_that_cannot_be_downloaded_or_is_not_an_image_fails_before_anything_is_sent_to_facebook(): void
    {
        foreach ([
            'missing' => Http::response('', 404),
            'not an image' => Http::response('<html>login</html>', 200, ['Content-Type' => 'text/html']),
            'empty' => Http::response('', 200),
            'redirect' => Http::response('', 302, ['Location' => 'https://elsewhere.test/a.png']),
        ] as $label => $answer) {
            Http::fake(['example.test/*' => $answer, 'graph.facebook.com/*' => Http::response(['id' => '9001_1'])]);

            $result = $this->adapter()->publish($this->delivery(null, 'https://example.test/a.png'), $this->account());

            $this->assertFalse($result->success, $label);
            $this->assertSame('image', $result->errorCode, $label);
            $this->assertSame([], $this->graphRequests(), $label.': Facebook must not be called.');
        }
    }

    public function test_a_picture_inside_our_network_is_never_requested(): void
    {
        Http::fake();

        // The host differs from the article's host and is a private address: refused without any request.
        $result = $this->adapter()->publish($this->delivery(null, 'https://10.0.0.5/secret.png'), $this->account());

        $this->assertFalse($result->success);
        $this->assertSame('image', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_a_refused_photo_post_gives_a_safe_message_without_the_token(): void
    {
        Http::fake([
            'example.test/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'bad '.self::TOKEN, 'code' => 324, 'error_subcode' => 2069019, 'fbtrace_id' => 'T']], 400),
        ]);

        $result = $this->adapter()->publish($this->delivery(null, 'https://example.test/a.png'), $this->account());

        $this->assertFalse($result->success);
        $this->assertStringNotContainsString(self::TOKEN, (string) $result->message);
        $this->assertStringNotContainsString('bad ', (string) $result->message);
        $this->assertStringContainsString('324/2069019', (string) $result->message, 'The Meta numbers are shown so the error can be looked up.');
    }

    public function test_publish_failure_gives_a_safe_message_without_the_token(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token '.self::TOKEN, 'code' => 190, 'fbtrace_id' => 'T']], 400)]);

        $result = $this->adapter()->publish($this->delivery(), $this->account());

        $this->assertFalse($result->success);
        $this->assertSame('auth_expired', $result->errorCode);
        $this->assertStringNotContainsString(self::TOKEN, (string) $result->message);
        $this->assertStringNotContainsString('Invalid token', (string) $result->message);
    }

    public function test_publish_without_a_page_token_does_not_call_facebook(): void
    {
        Http::fake();

        $result = $this->adapter()->publish($this->delivery(), $this->account(null));

        $this->assertFalse($result->success);
        $this->assertSame('no_token', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_remove_deletes_the_post_and_reports_a_refusal_with_its_own_message(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['success' => true])
            ->push(['error' => ['message' => 'forbidden', 'code' => 200]], 403)]);

        $first = $this->adapter()->remove($this->delivery('9001_555'), $this->account());
        $second = $this->adapter()->remove($this->delivery('9001_555'), $this->account());

        $this->assertTrue($first->success);
        $this->assertFalse($second->success);
        $this->assertSame('permission', $second->errorCode);
        $this->assertSame(__('admin.social.fb_remove_not_allowed'), $second->message);
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_contains($request->url(), '/v27.0/9001_555'));
    }

    public function test_sync_status_reads_the_address_and_names_a_missing_post(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['id' => '9001_555', 'permalink_url' => 'https://www.facebook.com/9001/posts/555', 'created_time' => '2026-11-11T01:00:00+0000'])
            ->push(['error' => ['message' => 'Unsupported get request', 'code' => 100, 'error_subcode' => 33]], 400)]);

        $found = $this->adapter()->syncStatus($this->delivery('9001_555'), $this->account());
        $missing = $this->adapter()->syncStatus($this->delivery('9001_555'), $this->account());

        $this->assertTrue($found->success);
        $this->assertSame('https://www.facebook.com/9001/posts/555', $found->remoteUrl);
        $this->assertFalse($missing->success);
        $this->assertSame('remote_missing', $missing->errorCode);
    }

    public function test_sync_metrics_reads_reactions_comments_and_shares(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'id' => '9001_555',
            'reactions' => ['data' => [], 'summary' => ['total_count' => 12]],
            'comments' => ['data' => [], 'summary' => ['total_count' => 3]],
            'shares' => ['count' => 2],
        ])]);

        $result = $this->adapter()->syncMetrics($this->delivery('9001_555'), $this->account());

        $this->assertTrue($result->success);
        $this->assertSame(['reactions' => 12, 'comments' => 3, 'shares' => 2], $result->data);
        Http::assertSent(fn (Request $request) => str_contains(urldecode($request->url()), 'reactions.summary(total_count).limit(0)')
            && str_contains(urldecode($request->url()), 'comments.summary(total_count).limit(0)'));
    }

    public function test_sync_metrics_counts_a_post_without_shares_as_zero(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '9001_555'])]);

        $result = $this->adapter()->syncMetrics($this->delivery('9001_555'), $this->account());

        $this->assertSame(['reactions' => 0, 'comments' => 0, 'shares' => 0], $result->data);
    }
}
