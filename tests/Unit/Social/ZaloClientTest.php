<?php

declare(strict_types=1);

namespace Tests\Unit\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\CmsBackend\Services\Social\Zalo\ZaloApiClient;
use Modules\CmsBackend\Services\Social\Zalo\ZaloApiException;
use Modules\CmsBackend\Services\Social\Zalo\ZaloSettings;
use Tests\TestCase;

/**
 * The Zalo client against a faked Zalo (never the real one), following the official documentation: OAuth v4 with PKCE, the token
 * exchange and refresh (secret_key header, form body), the OA API (access_token header, numeric error field). No database.
 */
final class ZaloClientTest extends TestCase
{
    private const TOKEN = 'OA-ACCESS-TOKEN-SECRET';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cmsbackend.social.zalo' => [
            'app_id' => '1234567890123', 'app_secret' => 'zalo-app-secret', 'redirect_uri' => 'https://example.test/cms/truyen-thong/tai-khoan/zalo/callback',
            'authorize_url' => 'https://oauth.zaloapp.com/v4/oa/permission', 'token_url' => 'https://oauth.zaloapp.com/v4/oa/access_token',
            'api_base' => 'https://openapi.zalo.me/v2.0/', 'refresh_token_months' => 3, 'access_token_margin' => 300,
            'verify_attempts' => 3, 'verify_delay_ms' => 0, 'timeout' => 5, 'connect_timeout' => 2, 'state_ttl' => 600,
        ]]);
    }

    private function client(): ZaloApiClient
    {
        return app(ZaloApiClient::class);
    }

    public function test_pkce_challenge_is_the_base64url_sha256_of_the_verifier_without_padding(): void
    {
        // The example of RFC 7636, appendix B.
        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', ZaloApiClient::challengeFor('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'));
    }

    public function test_authorization_url_carries_the_four_documented_parameters(): void
    {
        $url = $this->client()->authorizationUrl('STATE123', 'CHALLENGE456');

        $this->assertStringStartsWith('https://oauth.zaloapp.com/v4/oa/permission?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame([
            'app_id' => '1234567890123',
            'redirect_uri' => 'https://example.test/cms/truyen-thong/tai-khoan/zalo/callback',
            'code_challenge' => 'CHALLENGE456',
            'state' => 'STATE123',
        ], $query);
        $this->assertStringNotContainsString('zalo-app-secret', $url);
    }

    public function test_exchange_code_sends_the_secret_in_the_header_and_the_verifier_in_the_form(): void
    {
        Http::fake(['oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'])]);

        $tokens = $this->client()->exchangeCode('CODE1', 'VERIFIER43');

        $this->assertSame(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => 90000], $tokens);
        $request = Http::recorded()->first()[0];
        $this->assertSame('POST', $request->method());
        $this->assertSame('https://oauth.zaloapp.com/v4/oa/access_token', $request->url());
        $this->assertSame('zalo-app-secret', $request->header('secret_key')[0] ?? null);
        $this->assertSame(['code' => 'CODE1', 'app_id' => '1234567890123', 'grant_type' => 'authorization_code', 'code_verifier' => 'VERIFIER43'], $request->data());
    }

    public function test_refresh_uses_the_refresh_grant(): void
    {
        Http::fake(['oauth.zaloapp.com/*' => Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => '90000'])]);

        $tokens = $this->client()->refresh('RT1');

        $this->assertSame('RT2', $tokens['refresh_token']);
        $request = Http::recorded()->first()[0];
        $this->assertSame(['refresh_token' => 'RT1', 'app_id' => '1234567890123', 'grant_type' => 'refresh_token'], $request->data());
        $this->assertSame('zalo-app-secret', $request->header('secret_key')[0] ?? null);
    }

    public function test_a_refused_refresh_token_means_connect_again(): void
    {
        Http::fake(['oauth.zaloapp.com/*' => Http::response(['error' => -118, 'error_name' => 'invalid_grant', 'error_description' => 'refresh_token RT1 is invalid'], 400)]);

        try {
            $this->client()->refresh('RT1');
            $this->fail('A refused refresh token must raise an exception.');
        } catch (ZaloApiException $e) {
            $this->assertSame(ZaloApiException::AUTH_EXPIRED, $e->kind);
            $this->assertStringNotContainsString('RT1', $e->getMessage().$e->userMessage());
        }
    }

    public function test_a_server_error_of_the_oauth_endpoint_is_not_treated_as_a_dead_connection(): void
    {
        Http::fake(['oauth.zaloapp.com/*' => Http::response('oops', 503)]);

        try {
            $this->client()->refresh('RT1');
            $this->fail('A 503 must raise an exception.');
        } catch (ZaloApiException $e) {
            $this->assertSame(ZaloApiException::API, $e->kind);
        }
    }

    public function test_oa_info_reads_the_documented_fields_and_sends_the_token_in_the_header(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => 0, 'message' => 'Success', 'data' => [
            'oaid' => '4462152339089565647', 'name' => 'Trường ĐH Nam Bình Dương', 'description' => 'OA chính thức', 'oa_alias' => 'sbut',
            'is_verified' => true, 'oa_type' => 2, 'num_follower' => 1234, 'avatar' => 'https://s.zalo/avatar.jpg', 'cover' => 'javascript:alert(1)',
            'package_name' => 'Nâng cao',
        ]])]);

        $oa = $this->client()->oaInfo(self::TOKEN);

        $this->assertSame('4462152339089565647', $oa['oa_id']);
        $this->assertSame('Trường ĐH Nam Bình Dương', $oa['name']);
        $this->assertTrue($oa['is_verified']);
        $this->assertSame(1234, $oa['followers']);
        $this->assertSame('https://s.zalo/avatar.jpg', $oa['avatar']);
        $this->assertNull($oa['cover'], 'Only an https address may reach a view.');
        $request = Http::recorded()->first()[0];
        $this->assertSame('GET', $request->method());
        $this->assertStringStartsWith('https://openapi.zalo.me/v2.0/oa/getoa', $request->url());
        $this->assertSame(self::TOKEN, $request->header('access_token')[0] ?? null);
        $this->assertStringNotContainsString(self::TOKEN, $request->url());
    }

    public function test_zalo_error_numbers_map_to_kinds_without_leaking_the_answer(): void
    {
        $cases = [-216 => 'auth_expired', -220 => 'auth_expired', -214 => 'processing', -223 => 'permission', -221 => 'not_verified', -211 => 'quota', -224 => 'plan', -32 => 'rate_limit', -999 => 'api'];
        // One fake with one answer per call (a repeated Http::fake would keep answering with the first one).
        $answers = Http::sequence();
        foreach (array_keys($cases) as $number) {
            $answers->push(['error' => $number, 'message' => 'bad '.self::TOKEN]);
        }
        Http::fake(['openapi.zalo.me/*' => $answers]);
        foreach ($cases as $number => $kind) {
            try {
                $this->client()->oaInfo(self::TOKEN);
                $this->fail("Error {$number} must raise an exception.");
            } catch (ZaloApiException $e) {
                $this->assertSame($kind, $e->kind, (string) $number);
                $this->assertSame($number, $e->zaloCode);
                $this->assertStringNotContainsString(self::TOKEN, $e->getMessage().$e->userMessage());
            }
        }
    }

    public function test_plan_error_has_its_own_message_and_an_unknown_number_is_shown_to_the_administrator(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::sequence()
            ->push(['error' => -224, 'message' => 'The OA needs to upgrade OA Tier Package to use this feature.'])
            ->push(['error' => -777, 'message' => 'something new'])]);

        try {
            $this->client()->createArticle(self::TOKEN, ['title' => 'T']);
            $this->fail('-224 must raise an exception.');
        } catch (ZaloApiException $e) {
            $this->assertSame(ZaloApiException::PLAN, $e->kind);
            $this->assertStringContainsString('-224', $e->userMessage());
            $this->assertStringNotContainsString('Tier Package', $e->userMessage(), "Zalo's raw text never reaches a screen.");
        }

        try {
            $this->client()->createArticle(self::TOKEN, ['title' => 'T']);
            $this->fail('-777 must raise an exception.');
        } catch (ZaloApiException $e) {
            $this->assertSame(ZaloApiException::API, $e->kind);
            $this->assertStringContainsString('-777', $e->userMessage());
            $this->assertStringNotContainsString('something new', $e->userMessage());
        }
    }

    public function test_the_log_keeps_zalos_short_description_with_the_token_redacted_and_limited(): void
    {
        $logged = [];
        Log::shouldReceive('warning')->andReturnUsing(function (string $message, array $context = []) use (&$logged): void {
            $logged = $context;
        });
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => -224, 'message' => "line1\nOA needs a plan ".self::TOKEN.str_repeat('x', 400)])]);

        try {
            $this->client()->createArticle(self::TOKEN, ['title' => 'T']);
            $this->fail('-224 must raise an exception.');
        } catch (ZaloApiException) {
        }

        $this->assertSame('article_create', $logged['call']);
        $this->assertSame(-224, $logged['code']);
        $this->assertStringContainsString('OA needs a plan [token]', $logged['zalo_message']);
        $this->assertStringNotContainsString(self::TOKEN, $logged['zalo_message']);
        $this->assertStringNotContainsString("\n", $logged['zalo_message']);
        $this->assertLessThanOrEqual(200, mb_strlen($logged['zalo_message']));
    }

    public function test_create_article_returns_the_progress_token_and_verify_returns_the_id_or_null_while_processing(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/article/create' => Http::response(['error' => 0, 'data' => ['token' => 'PROGRESS-1']]),
            'openapi.zalo.me/v2.0/article/verify' => Http::sequence()
                ->push(['error' => -214, 'message' => 'processing'])
                ->push(['error' => 0, 'data' => ['id' => 'ARTICLE-9']])]);

        $token = $this->client()->createArticle(self::TOKEN, ['type' => 'normal', 'title' => 'T']);
        $first = $this->client()->verifyArticle(self::TOKEN, $token);
        $second = $this->client()->verifyArticle(self::TOKEN, $token);

        $this->assertSame('PROGRESS-1', $token);
        $this->assertNull($first);
        $this->assertSame('ARTICLE-9', $second);
        $create = Http::recorded()->first()[0];
        $this->assertSame(self::TOKEN, $create->header('access_token')[0] ?? null);
        $this->assertSame(['type' => 'normal', 'title' => 'T'], $create->data());
    }

    public function test_article_list_reads_views_shares_status_and_the_https_address(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => 0, 'data' => ['total' => 2, 'medias' => [
            ['id' => 'A1', 'title' => 'T', 'status' => 'show', 'total_view' => 1, 'total_share' => 0, 'create_date' => 1690260497732, 'update_date' => 1690260504041, 'thumb' => 'https://t', 'link_view' => 'https://zalo.me/a/A1'],
            ['id' => 'A2', 'status' => 'hide', 'link_view' => 'http://insecure'],
            ['title' => 'no id'],
        ]]])]);

        $list = $this->client()->articleList(self::TOKEN, 0, 10);

        $this->assertSame(2, $list['total']);
        $this->assertSame([
            ['id' => 'A1', 'status' => 'show', 'views' => 1, 'shares' => 0, 'link' => 'https://zalo.me/a/A1'],
            ['id' => 'A2', 'status' => 'hide', 'views' => 0, 'shares' => 0, 'link' => null],
        ], $list['items']);
        $request = Http::recorded()->first()[0];
        $this->assertSame(self::TOKEN, $request->header('access_token')[0] ?? null);
        $this->assertStringStartsWith('https://openapi.zalo.me/v2.0/article/getslice?', $request->url());
    }

    public function test_settings_report_what_is_wrong_without_revealing_values(): void
    {
        $settings = app(ZaloSettings::class);
        $this->assertTrue($settings->configured());

        config(['cmsbackend.social.zalo.app_id' => 'abc']);
        $this->assertSame('zalo_config_app_id', $settings->problem());
        config(['cmsbackend.social.zalo.app_id' => '1234567890123', 'cmsbackend.social.zalo.app_secret' => '']);
        $this->assertSame('zalo_config_app_secret', $settings->problem());
        config(['cmsbackend.social.zalo.app_secret' => 's', 'cmsbackend.social.zalo.redirect_uri' => 'http://example.test/cb']);
        $this->assertSame('zalo_config_redirect_https', $settings->problem());
        config(['cmsbackend.social.zalo.redirect_uri' => 'http://127.0.0.1:8011/cb']);
        $this->assertNull($settings->problem());
        config(['cmsbackend.social.zalo.redirect_uri' => 'https://example.test/cb?x=1']);
        $this->assertSame('zalo_config_redirect_invalid', $settings->problem());
    }
}
