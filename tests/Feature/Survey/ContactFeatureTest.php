<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use App\DataProviders\MenuItemDataProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\FeatureRegistry;
use Modules\CmsBackend\Services\Developer\PermissionAvailability;
use Modules\CmsFrontend\Models\LienHe;
use Modules\CmsFrontend\Services\EditorialLayout;

/**
 * Liên hệ – Góp ý trong Feature Registry: lưu địa chỉ, giới hạn nội dung, honeypot, rate limit, quyền, xóa mềm,
 * cổng chức năng và menu. Chạy trên SQLite :memory: (xem SurveySqliteTestCase); bảng `lienhe` dựng bằng chính các file migration.
 */
final class ContactFeatureTest extends SurveySqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require base_path('Modules/CmsBackend/database/migrations/2025_06_16_081930_create_lienhe_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_000001_add_address_and_text_message_to_lienhe_table.php'))->up();
        $this->withErrorPageSupport();
    }

    private function registry(): FeatureRegistry
    {
        return app(FeatureRegistry::class);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'name' => 'Nguyễn Văn A', 'email' => 'a@example.com', 'phone' => '0901 234 567',
            'address' => 'Đơn vị Thử nghiệm', 'subject' => 'Góp ý thử', 'message' => 'Nội dung góp ý đủ dài để hợp lệ.',
        ];
    }

    private function record(array $over = []): LienHe
    {
        return LienHe::create($over + $this->payload());
    }

    /** @return array<int, string> nhãn quyền */
    private function labels(PermissionsEnum ...$cases): array
    {
        return array_map(fn (PermissionsEnum $c) => $c->label(), $cases);
    }

    /** Trang đầy đủ cần nhiều bảng của site (quảng cáo, menu...) mà SQLite thử nghiệm không dựng: chỉ kiểm tra không bị cổng chức năng chặn. */
    private function assertPageReachable(string $uri): void
    {
        $this->assertNotSame(404, $this->get($uri)->getStatusCode(), $uri.' phải qua được cổng chức năng');
    }

    // ------------------------------------------------------------------ gửi biểu mẫu

    public function test_valid_submission_is_saved_with_address(): void
    {
        $this->post('/lien-he', $this->payload())->assertRedirect();
        $row = DB::table('lienhe')->first();
        $this->assertSame('Đơn vị Thử nghiệm', $row->address);
        $this->assertSame('a@example.com', $row->email);
        $this->assertNull($row->deleted_at);
        $this->assertSame(1, DB::table('lienhe')->count());
    }

    public function test_message_limit_is_5000_characters(): void
    {
        $this->post('/lien-he', $this->payload(['message' => str_repeat('a', 5000)]))->assertSessionHasNoErrors();
        $this->assertSame(5000, mb_strlen((string) DB::table('lienhe')->value('message')));

        $this->post('/lien-he', $this->payload(['message' => str_repeat('a', 5001)]))->assertSessionHasErrors('message');
        $this->assertSame(1, DB::table('lienhe')->count(), 'nội dung quá giới hạn bị từ chối, không ghi DB');
    }

    public function test_validation_rules(): void
    {
        foreach ([
            ['name' => str_repeat('a', 256)], ['email' => 'khong-phai-email'], ['phone' => 'abc'], ['phone' => str_repeat('1', 31)],
            ['address' => ''], ['address' => str_repeat('a', 256)], ['subject' => ''], ['message' => 'ngắn'],
        ] as $bad) {
            \Illuminate\Support\Facades\Cache::flush(); // mỗi ca một lượt: tránh chạm giới hạn 5 lần/10 phút
            $this->post('/lien-he', $this->payload($bad))->assertSessionHasErrors(array_key_first($bad));
        }
        $this->assertSame(0, DB::table('lienhe')->count());
        \Illuminate\Support\Facades\Cache::flush();
        $this->flushSession();
        $this->post('/lien-he', $this->payload(['phone' => str_repeat('1', 30)]))->assertSessionHasNoErrors();
    }

    public function test_honeypot_is_rejected_and_nothing_is_saved(): void
    {
        $this->post('/lien-he', $this->payload(['website' => 'http://spam.example']))->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, DB::table('lienhe')->count());
        $this->post('/lien-he', $this->payload(['website' => '']))->assertSessionHas('success');
        $this->assertSame(1, DB::table('lienhe')->count());
        $this->assertFalse(Schema::hasColumn('lienhe', 'website'), 'honeypot không được lưu');
    }

    public function test_contact_write_rate_limit_is_5_per_10_minutes_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/lien-he', $this->payload())->assertSessionHas('success');
        }
        $this->post('/lien-he', $this->payload())->assertRedirect()->assertSessionHas('error', __('messages.contact_throttled'));
        $this->assertSame(5, DB::table('lienhe')->count());
        $this->postJson('/lien-he', $this->payload())->assertStatus(429)->assertJsonPath('message', __('messages.contact_throttled'));
    }

    public function test_every_public_post_route_including_locales_has_the_limiter_and_the_gate(): void
    {
        $posts = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_ends_with($r->uri(), 'lien-he') && in_array('POST', $r->methods(), true));
        $this->assertGreaterThanOrEqual(2, $posts->count(), 'có bản mặc định và bản theo ngôn ngữ (/en/lien-he)');
        foreach ($posts as $route) {
            $this->assertContains('throttle:contact-write', $route->gatherMiddleware(), $route->uri());
            $this->assertContains('cms.feature:contact', $route->gatherMiddleware(), $route->uri());
        }
        foreach (collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_ends_with($r->uri(), 'lien-he') && in_array('GET', $r->methods(), true)) as $route) {
            if (! str_starts_with($route->uri(), 'cms/')) {
                $this->assertContains('cms.feature:contact', $route->gatherMiddleware(), $route->uri());
            }
        }
    }

    // ------------------------------------------------------------------ route quản trị

    public function test_admin_routes_are_only_the_five_allowed_ones(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->getName())->filter(fn ($n) => is_string($n) && str_contains($n, 'dashboard.lien-he.'))->values()->all();
        $this->assertEqualsCanonicalizing(['cms.dashboard.lien-he.index', 'cms.dashboard.lien-he.show', 'cms.dashboard.lien-he.destroy', 'cms.dashboard.lien-he.restore', 'cms.dashboard.lien-he.purge'], $names);
        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('cms.dashboard.lien-he.index')->methods());
        foreach (['destroy', 'restore', 'purge'] as $action) {
            $this->assertSame(['POST'], Route::getRoutes()->getByName("cms.dashboard.lien-he.$action")->methods());
        }
        $this->assertSame('cms/lien-he/{id}/xoa-vinh-vien', Route::getRoutes()->getByName('cms.dashboard.lien-he.purge')->uri());
        foreach (['tao-moi', 'create', 'chinh-sua'] as $path) {
            $this->get('/cms/lien-he/'.$path)->assertNotFound();
        }
        foreach (['put', 'patch', 'delete'] as $verb) {
            $this->{$verb}('/cms/lien-he/1')->assertStatus(405);
        }
    }

    public function test_user_without_permission_cannot_see_list_or_detail(): void
    {
        $record = $this->record();
        $this->actingAs($this->user([]));
        $this->get('/cms/lien-he')->assertForbidden();
        $this->get('/cms/lien-he/'.$record->id)->assertForbidden();
        $this->actingAs($this->user($this->labels(PermissionsEnum::KHAOSAT_VIEW)));
        $this->get('/cms/lien-he/'.$record->id)->assertForbidden();
    }

    public function test_view_permission_or_parent_can_see_but_cannot_delete(): void
    {
        $record = $this->record();
        foreach ([[PermissionsEnum::LIENHE_VIEW], [PermissionsEnum::LIENHE_MANAGER]] as $granted) {
            $this->actingAs($this->user($this->labels(...$granted)));
            $show = $this->get('/cms/lien-he/'.$record->id);
            $show->assertOk()->assertSee('Đơn vị Thử nghiệm');
        }
        $this->actingAs($this->user($this->labels(PermissionsEnum::LIENHE_VIEW)));
        $this->post("/cms/lien-he/{$record->id}/xoa")->assertForbidden();
        $this->post("/cms/lien-he/{$record->id}/khoi-phuc")->assertForbidden();
        $this->post("/cms/lien-he/{$record->id}/xoa-vinh-vien")->assertForbidden();
        $this->assertNull(DB::table('lienhe')->where('id', $record->id)->value('deleted_at'), 'chỉ có quyền xem thì không xóa được');
        $this->get('/cms/lien-he/'.$record->id)->assertDontSee('/xoa"', false);
    }

    public function test_delete_permission_soft_deletes_restores_and_purges(): void
    {
        $record = $this->record();
        $this->actingAs($this->user($this->labels(PermissionsEnum::LIENHE_VIEW, PermissionsEnum::LIENHE_DELETE)));

        $this->get('/cms/lien-he/'.$record->id)->assertOk()->assertSee("/cms/lien-he/{$record->id}/xoa", false);

        $this->post("/cms/lien-he/{$record->id}/xoa")->assertRedirect();
        $this->assertNotNull(DB::table('lienhe')->where('id', $record->id)->value('deleted_at'), 'xóa mềm chỉ ghi deleted_at');
        $this->assertSame(1, DB::table('lienhe')->count());
        $this->get('/cms/lien-he')->assertOk()->assertDontSee('Góp ý thử');
        $this->get('/cms/lien-he?status=trash')->assertOk()->assertSee('Góp ý thử')->assertSee("/cms/lien-he/{$record->id}/khoi-phuc", false)->assertSee("/cms/lien-he/{$record->id}/xoa-vinh-vien", false);

        $this->post("/cms/lien-he/{$record->id}/khoi-phuc")->assertRedirect();
        $this->assertNull(DB::table('lienhe')->where('id', $record->id)->value('deleted_at'));

        $this->post("/cms/lien-he/{$record->id}/xoa-vinh-vien")->assertRedirect();
        $this->assertSame(1, DB::table('lienhe')->count(), 'bản ghi chưa vào thùng rác thì không xóa vĩnh viễn được');

        $this->post("/cms/lien-he/{$record->id}/xoa")->assertRedirect();
        $this->post("/cms/lien-he/{$record->id}/xoa-vinh-vien")->assertRedirect();
        $this->assertSame(0, DB::table('lienhe')->count());
    }

    public function test_search_covers_subject_name_email_and_phone(): void
    {
        $this->record(['subject' => 'Học phí', 'name' => 'Trần Bình', 'email' => 'binh@example.com', 'phone' => '0912345678']);
        $this->record(['subject' => 'Tuyển sinh', 'name' => 'Lê Cường', 'email' => 'cuong@example.com', 'phone' => '0987654321']);
        $this->actingAs($this->user($this->labels(PermissionsEnum::LIENHE_VIEW)));
        foreach (['Học phí' => 'Tuyển sinh', 'Trần Bình' => 'Lê Cường', 'binh@example' => 'cuong@example', '0912345678' => '0987654321'] as $term => $absent) {
            $this->get('/cms/lien-he?filter='.urlencode($term))->assertOk()->assertSee($term)->assertDontSee($absent);
        }
    }

    public function test_legacy_record_without_address_is_still_viewable_and_content_is_escaped(): void
    {
        $id = DB::table('lienhe')->insertGetId(['name' => '<script>alert(1)</script>', 'email' => 'cu@example.com', 'phone' => '0900000000', 'subject' => '<b>Cũ</b>', 'message' => '<img src=x onerror=alert(1)> nội dung cũ', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->user($this->labels(PermissionsEnum::LIENHE_VIEW)));
        $html = $this->get('/cms/lien-he/'.$id)->assertOk()->getContent();
        $this->assertStringContainsString(__('admin.contact.no_address'), $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('data-copy="cu@example.com"', $html, 'sao chép qua data-*, không nhúng vào JavaScript inline');
        $this->assertStringNotContainsString("copyToClipboard('", $html);
    }

    // ------------------------------------------------------------------ Feature Registry

    public function test_contact_feature_is_registered_with_translated_names(): void
    {
        $d = $this->registry()->find('contact');
        $this->assertNotNull($d);
        $this->assertTrue($d->enabledByDefault());
        $this->assertSame('cms.dashboard.lien-he.index', $d->adminRoute());
        $this->assertSame([['key' => 'index', 'name' => $d->publicTargets()[0]['name'], 'route' => 'cms.lien-he']], $d->publicTargets());
        $this->assertSame(['lienhe-manager', 'lienhe-view', 'lienhe-delete'], $d->permissions());
        $this->assertSame('/lien-he', parse_url((string) $this->registry()->url('contact', 'index'), PHP_URL_PATH));
        $this->assertSame('/cms/lien-he', parse_url((string) $this->registry()->adminUrl('contact'), PHP_URL_PATH));

        $original = app()->getLocale();
        try {
            foreach (['vi' => ['Liên hệ – Góp ý', 'Trang liên hệ'], 'en' => ['Contact', 'Contact page']] as $locale => [$name, $target]) {
                app()->setLocale($locale);
                $this->assertSame($name, $this->registry()->find('contact')->name());
                $this->assertSame($target, $this->registry()->find('contact')->publicTargets()[0]['name']);
                $choice = collect($this->registry()->menuChoices())->firstWhere('key', 'contact');
                $this->assertSame($name, $choice['name']);
                $this->assertSame($target, $choice['targets'][0]['name']);
            }
        } finally {
            app()->setLocale($original);
        }
        $this->assertSame([], $this->registry()->errors());
    }

    public function test_feature_off_returns_404_blocks_permissions_and_keeps_data_then_restores(): void
    {
        $record = $this->record();
        $this->actingAs($this->user($this->labels(PermissionsEnum::LIENHE_VIEW, PermissionsEnum::LIENHE_DELETE)));
        $this->get('/cms/lien-he')->assertOk();
        $this->assertPageReachable('/lien-he');

        $this->registry()->setEnabled('contact', false);
        $this->get('/lien-he')->assertNotFound();
        $this->post('/lien-he', $this->payload())->assertNotFound();
        $this->get('/en/lien-he')->assertNotFound();
        $this->post('/en/lien-he', $this->payload())->assertNotFound();
        $this->get('/cms/lien-he')->assertNotFound();
        $this->get('/cms/lien-he/'.$record->id)->assertNotFound();
        $this->post("/cms/lien-he/{$record->id}/xoa")->assertNotFound();
        $this->assertSame(1, DB::table('lienhe')->count(), 'dữ liệu lienhe không bị xóa');
        $this->assertNull(DB::table('lienhe')->value('deleted_at'));

        $availability = app(PermissionAvailability::class);
        foreach (['lienhe-manager', 'lienhe-view', 'lienhe-delete'] as $slug) {
            $this->assertFalse($availability->isAvailableSlug($slug), "$slug bị khóa khi chức năng tắt");
            $this->assertSame('contact', $this->registry()->featureOfPermission($slug));
            $this->assertTrue($this->registry()->permissionBlocked($slug));
        }

        $this->registry()->setEnabled('contact', true);
        $this->assertPageReachable('/lien-he');
        $this->get('/cms/lien-he/'.$record->id)->assertOk()->assertSee('Đơn vị Thử nghiệm');
        $this->assertTrue($availability->isAvailableSlug('lienhe-view'));
    }

    public function test_theme_does_not_decide_the_contact_feature(): void
    {
        foreach (['default', 'nam-binh-duong', 'ban-de-an'] as $theme) {
            $this->theme($theme);
            $this->assertPageReachable('/lien-he');
        }
        $this->registry()->setEnabled('contact', false);
        foreach (['default', 'nam-binh-duong', 'ban-de-an'] as $theme) {
            $this->theme($theme);
            $this->get('/lien-he')->assertNotFound();
        }
    }

    public function test_form_of_every_theme_has_maxlength_and_honeypot(): void
    {
        foreach (['default', 'nam-binh-duong', 'ban-de-an'] as $theme) {
            $html = (string) file_get_contents(base_path("Modules/CmsFrontend/resources/views/screens/$theme/pages/frontend/lien-he/index.blade.php"));
            foreach (['name' => 255, 'email' => 255, 'phone' => 30, 'address' => 255, 'subject' => 255] as $field => $max) {
                $this->assertMatchesRegularExpression('/name="'.$field.'" id="'.$field.'" maxlength="'.$max.'"/', $html, "$theme: $field");
            }
            $this->assertMatchesRegularExpression('/<textarea name="message" id="message" minlength="10" maxlength="5000"/', $html, "$theme: message");
            $this->assertStringContainsString('contact-honeypot', $html, "$theme: honeypot");
        }
        $honeypot = view('cmsfrontend::screens.components.contact-honeypot')->render();
        $this->assertStringContainsString('name="website"', $honeypot);
        $this->assertStringContainsString('aria-hidden="true"', $honeypot);
        $this->assertStringContainsString('tabindex="-1"', $honeypot);
    }

    public function test_ban_de_an_home_form_is_not_embedded_when_feature_is_off(): void
    {
        $block = file_get_contents(base_path('Modules/CmsFrontend/resources/views/screens/ban-de-an/components/home/blocks/contact.blade.php'));
        $this->assertMatchesRegularExpression("/isEnabled\('contact'\)\)\s*@include\('cmsfrontend::screens\.components\.home\.proposal-contact'\)\s*@endif/", (string) $block);
        $form = (string) file_get_contents(base_path('Modules/CmsFrontend/resources/views/screens/ban-de-an/components/home/proposal-contact.blade.php'));
        $this->assertStringContainsString('contact-honeypot', $form);
        $this->assertStringContainsString('maxlength="5000"', $form);
        $this->assertStringContainsString('maxlength="30"', $form);
    }

    // ------------------------------------------------------------------ menu

    private function menuTables(): void
    {
        Schema::create('menus', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('description')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('menu_items', function ($t) {
            $t->integer('menu_id');
            $t->id();
            $t->string('title');
            $t->integer('parent_id')->default(0);
            $t->string('treepath');
            $t->string('url');
            $t->string('type')->default('url');
            $t->integer('ref_id');
            $t->string('target')->default('_self');
            $t->string('icon', 100)->nullable();
            $t->integer('ordinal')->default(0);
            $t->string('feature_key', 100)->nullable();
            $t->string('feature_target', 100)->nullable();
            $t->timestamps();
        });
        Schema::create('menu_item_translations', function ($t) {
            $t->id();
            $t->unsignedBigInteger('menu_item_id');
            $t->string('locale', 10);
            $t->string('title')->nullable();
            $t->string('custom_url')->nullable();
            $t->timestamps();
        });
        DB::table('menus')->insert(['id' => 1, 'name' => 'Menu thử']);
    }

    /** @return array<int, array<string, mixed>> */
    private function publicMenu(): array
    {
        $items = (new MenuItemDataProvider)->getMenuItemByMenuId(1, true);
        $navigation = new \ReflectionMethod(EditorialLayout::class, 'navigation');

        return $navigation->invoke(app(EditorialLayout::class), $items);
    }

    public function test_feature_menu_for_contact_hides_and_returns_but_manual_links_stay(): void
    {
        $this->menuTables();
        (new MenuItemDataProvider)->saveAll(1, [
            ['id' => -1, 'title' => 'Liên hệ', 'type' => 'feature', 'url' => 'x', 'target' => '_self', 'ref_id' => 0, 'feature_key' => 'contact', 'feature_target' => 'index'],
            ['id' => -2, 'title' => 'Liên kết liên hệ tự tạo', 'type' => 'url', 'url' => '/lien-he', 'target' => '_self', 'ref_id' => 0],
        ]);

        $menu = $this->publicMenu();
        $this->assertSame(['Liên hệ', 'Liên kết liên hệ tự tạo'], array_column($menu, 'title'));
        $this->assertSame('/lien-he', parse_url($menu[0]['url'], PHP_URL_PATH));

        $this->registry()->setEnabled('contact', false);
        $this->assertSame(['Liên kết liên hệ tự tạo'], array_column($this->publicMenu(), 'title'), 'chỉ menu loại chức năng tự ẩn');
        $this->assertSame(2, DB::table('menu_items')->count(), 'không xóa dữ liệu menu');

        $this->registry()->setEnabled('contact', true);
        $this->assertSame(['Liên hệ', 'Liên kết liên hệ tự tạo'], array_column($this->publicMenu(), 'title'));
    }

    // ------------------------------------------------------------------ liên kết do mã nguồn tạo

    private const THEMES = ['default', 'nam-binh-duong', 'ban-de-an'];

    private function viewsDir(): string
    {
        return base_path('Modules/CmsFrontend/resources/views/screens');
    }

    /** @return array<int, string> mọi view theme (trừ trang liên hệ/biểu mẫu) có nhắc tới route/URL liên hệ */
    private function viewsLinkingToContact(): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->viewsDir(), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (! str_ends_with($path, '.blade.php') || str_contains($path, '/lien-he/index.blade.php') || str_contains($path, 'proposal-contact.blade.php')) {
                continue;
            }
            if (preg_match("/cms\.lien-he|'\/lien-he/", (string) file_get_contents($path))) {
                $found[] = $path;
            }
        }

        return $found;
    }

    public function test_every_system_generated_contact_link_in_every_theme_is_guarded_by_the_feature(): void
    {
        $files = $this->viewsLinkingToContact();
        $this->assertGreaterThanOrEqual(15, count($files), 'phải quét được các view theme có liên kết liên hệ');
        foreach (self::THEMES as $theme) {
            $this->assertNotEmpty(array_filter($files, fn ($f) => str_contains($f, "/screens/$theme/")), $theme);
        }
        $guard = "isEnabled('contact')";
        foreach ($files as $path) {
            $code = (string) file_get_contents($path);
            preg_match_all("/cms\.lien-he|'\/lien-he/", $code, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as [$text, $offset]) {
                $before = substr($code, max(0, $offset - 700), min($offset, 700));
                $this->assertSame(0, preg_match('/[A-Za-z0-9_]@(?:if|endif|else)/', substr($code, max(0, $offset - 700), 800)), basename($path).': @if/@endif dính liền chữ nên Blade không biên dịch');
                $this->assertStringContainsString($guard, $before, basename(dirname($path)).'/'.basename($path).' @'.$offset.': liên kết liên hệ phải nằm trong @if(...isEnabled(\'contact\'))');
            }
        }
        $this->assertStringContainsString("isEnabled('contact')", (string) file_get_contents(base_path('Modules/CmsFrontend/app/Services/EditorialLayout.php')));
    }

    public function test_rendered_partials_show_contact_links_when_on_and_hide_them_when_off(): void
    {
        $errors = new \Illuminate\Support\ViewErrorBag;
        $cases = [
            ['default', 'cmsfrontend::screens.components.home.partners-support', ['/lien-he']],
            ['ban-de-an', 'cmsfrontend::screens.components.home.proposal-quick-links', ['href="#lien-he"']],
            ['ban-de-an', 'cmsfrontend::screens.components.home.proposal-about', ['/lien-he'], ['proposalIntroduction' => null, 'proposalPartners' => collect()]],
            ['nam-binh-duong', 'cmsfrontend::screens.components.home.introduction', ['/lien-he'], ['introduction' => null]],
        ];
        foreach ($cases as $case) {
            [$theme, $view, $needles] = $case;
            $data = ($case[3] ?? []) + ['errors' => $errors];
            $this->theme($theme);
            $this->registry()->setEnabled('contact', true);
            $on = view($view, $data)->render();
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $on, "$theme $view bật");
            }
            $this->registry()->setEnabled('contact', false);
            $off = view($view, $data)->render();
            foreach ($needles as $needle) {
                $this->assertStringNotContainsString($needle, $off, "$theme $view tắt");
            }
            $this->assertStringNotContainsString('/lien-he', $off, "$theme $view: không còn liên kết /lien-he khi tắt");
        }
    }

    public function test_user_configured_url_menu_to_contact_is_not_removed_or_edited_when_feature_is_off(): void
    {
        $this->menuTables();
        (new MenuItemDataProvider)->saveAll(1, [['id' => -1, 'title' => 'Liên hệ do người dùng nhập', 'type' => 'url', 'url' => '/lien-he', 'target' => '_self', 'ref_id' => 0]]);
        $before = DB::table('menu_items')->first();
        $this->registry()->setEnabled('contact', false);
        $this->assertSame(['Liên hệ do người dùng nhập'], array_column($this->publicMenu(), 'title'));
        $this->assertEquals($before, DB::table('menu_items')->first(), 'menu loại URL không bị sửa');
        $this->assertSame('/lien-he', DB::table('menu_items')->value('url'));
    }

    public function test_breadcrumb_and_hidden_subject_follow_the_locale_and_never_ship_hardcoded_vietnamese(): void
    {
        $original = app()->getLocale();
        $errors = new \Illuminate\Support\ViewErrorBag;
        try {
            foreach (['vi' => ['Liên hệ', 'Liên hệ Ban Đề án'], 'en' => ['Contact', 'Contact the Project Office']] as $locale => [$crumb, $subject]) {
                app()->setLocale($locale);
                $this->assertStringContainsString($crumb, (string) \Diglactic\Breadcrumbs\Breadcrumbs::render('cms.lien-he'), "breadcrumb $locale");
                $this->theme('ban-de-an');
                $form = view('cmsfrontend::screens.components.home.proposal-contact', ['errors' => $errors])->render();
                $this->assertMatchesRegularExpression('/name="subject" value="'.preg_quote($subject, '/').'"/u', $form, "subject $locale");
            }
            app()->setLocale('en');
            $this->assertStringNotContainsString('Liên hệ Ban Đề án', view('cmsfrontend::screens.components.home.proposal-contact', ['errors' => $errors])->render());
        } finally {
            app()->setLocale($original);
        }
        $this->assertStringNotContainsString('"Liên hệ"', (string) file_get_contents(base_path('Modules/CmsFrontend/routes/breadcrumbs/LienHeBreadcrumb.php')));
    }

    public function test_contact_views_contain_no_hardcoded_vietnamese_text_or_form_data(): void
    {
        $diacritics = '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu';
        $files = array_merge(
            glob($this->viewsDir().'/*/pages/frontend/lien-he/index.blade.php'),
            [$this->viewsDir().'/ban-de-an/components/home/proposal-contact.blade.php', $this->viewsDir().'/default/components/contact-honeypot.blade.php'],
            glob(base_path('Modules/CmsBackend/resources/views/screens/default/pages/lien-he/*.blade.php')),
        );
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            $code = preg_replace('/\{\{--.*?--\}\}/s', '', $code) ?? '';
            $this->assertSame(0, preg_match($diacritics, $code), basename(dirname($file)).'/'.basename($file).' còn chữ tiếng Việt viết cứng');
        }
    }

    // ------------------------------------------------------------------ migration, thống kê, ngôn ngữ

    public function test_migration_keeps_existing_rows_and_is_repeatable(): void
    {
        Schema::drop('lienhe');
        (require base_path('Modules/CmsBackend/database/migrations/2025_06_16_081930_create_lienhe_table.php'))->up();
        DB::table('lienhe')->insert([
            ['name' => 'Cũ 1', 'email' => 'c1@example.com', 'phone' => '0900000001', 'subject' => 'S1', 'message' => 'Nội dung cũ một', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cũ 2', 'email' => 'c2@example.com', 'phone' => '0900000002', 'subject' => 'S2', 'message' => 'Nội dung cũ hai', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('lienhe')->where('name', 'Cũ 2')->update(['deleted_at' => now()]);
        $before = DB::table('lienhe')->count();

        $migration = require base_path('database/migrations/2026_11_02_000001_add_address_and_text_message_to_lienhe_table.php');
        $migration->up();
        $migration->up();

        $this->assertSame($before, DB::table('lienhe')->count());
        $this->assertSame(['Nội dung cũ một', 'Nội dung cũ hai'], DB::table('lienhe')->orderBy('id')->pluck('message')->all());
        $this->assertSame([null, null], DB::table('lienhe')->orderBy('id')->pluck('address')->all(), 'bản ghi cũ: địa chỉ trống');
        $this->assertSame(1, DB::table('lienhe')->whereNotNull('deleted_at')->count());
        $this->assertTrue(Schema::hasColumn('lienhe', 'address'));
        $this->assertContains(strtolower(Schema::getColumnType('lienhe', 'message')), ['text', 'mediumtext', 'longtext']);
    }

    public function test_monthly_statistics_are_in_chronological_order_and_skip_trash(): void
    {
        foreach (['2025-12-05', '2026-01-10', '2026-01-20', '2025-02-01'] as $day) {
            $this->record()->forceFill(['created_at' => $day.' 10:00:00'])->save();
        }
        $this->record()->forceFill(['created_at' => '2026-03-01 10:00:00'])->save();
        LienHe::query()->where('created_at', '2026-03-01 10:00:00')->first()->delete();

        $stats = app(\Modules\CmsBackend\Services\LienHeService::class)->thongKeTheoThang();
        $this->assertSame([['x' => '02-2025', 'y' => 1], ['x' => '12-2025', 'y' => 1], ['x' => '01-2026', 'y' => 2]], $stats);
        $this->assertSame([['x' => '01-2026', 'y' => 2]], app(\Modules\CmsBackend\Services\LienHeService::class)->thongKeTheoThang(2026));
    }

    public function test_vi_and_en_translations_have_matching_keys(): void
    {
        $flatten = function (array $tree, string $prefix = '') use (&$flatten): array {
            $keys = [];
            foreach ($tree as $key => $value) {
                is_array($value) ? $keys = array_merge($keys, $flatten($value, $prefix.$key.'.')) : $keys[] = $prefix.$key;
            }
            sort($keys);

            return $keys;
        };
        foreach (['admin' => ['contact'], 'features' => ['contact'], 'messages' => null] as $file => $groups) {
            $vi = require base_path("lang/vi/$file.php");
            $en = require base_path("lang/en/$file.php");
            foreach ($groups ?? [null] as $group) {
                $a = $group ? $vi[$group] : $vi;
                $b = $group ? $en[$group] : $en;
                $this->assertSame($flatten($a), $flatten($b), "$file.".($group ?? '*').' lệch khóa VI/EN');
            }
        }
        foreach (['contact_throttled', 'content_max_5000'] as $key) {
            $this->assertNotSame("messages.$key", __("messages.$key"));
        }
    }

    public function test_internal_status_name_is_feature_blocked_everywhere(): void
    {
        foreach (['vi', 'en'] as $locale) {
            $admin = require base_path("lang/$locale/admin.php");
            $this->assertArrayHasKey('feature_blocked', $admin['developer']);
            $this->assertArrayNotHasKey('theme_blocked', $admin['developer']);
        }
    }
}
