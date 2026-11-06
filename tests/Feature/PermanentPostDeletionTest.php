<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\CmsBackend\Services\PostPurger;
use Modules\CmsBackend\Services\UploadReferences;
use Modules\CmsBackend\Support\EditorialPermissions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class PermanentPostDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cmsbackend.editorial_database' => ':memory:']);
        DB::purge('sqlite');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key): void {
                $table->unsignedBigInteger($key);
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
            });
        }
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->increments('term_id');
            $table->string('slug');
            $table->string('name');
        });
        Schema::create('term_taxonomy', function (Blueprint $table): void {
            $table->increments('term_taxonomy_id');
            $table->unsignedInteger('term_id');
            $table->string('taxonomy');
            $table->string('loai_chuyen_muc')->nullable();
        });
        Schema::create('user_term_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('term_id');
            $table->boolean('can_create')->default(false);
            $table->softDeletes();
        });
        Schema::create('term_relationships', function (Blueprint $table): void {
            $table->unsignedBigInteger('object_id');
            $table->string('object_type');
            $table->unsignedInteger('term_taxonomy_id');
            $table->integer('term_order')->default(0);
        });
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('post_type')->default('post');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('post_parent')->nullable();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('render_file_in_view')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('cms_editorial_meta', function (Blueprint $table): void {
            $table->unsignedBigInteger('post_id')->primary();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
        foreach (['cms_editorial_history', 'comments', 'feedback'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('post_id');
                $table->text('content')->nullable();
            });
        }
        Schema::create('posts_banners', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('ref_id')->nullable();
        });
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('ref_id')->nullable();
        });
        Schema::create('options', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
        });
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('object_id');
            $table->text('translated_value');
        });
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('uuid')->nullable();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });
        Storage::fake('cms_media');
        config(['filesystems.disks.cms_media.url' => 'http://localhost/media/cms', 'filesystems.disks.cms_media.root' => Storage::disk('cms_media')->path('')]);
    }

    private function actor(bool $admin = true): User
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create());
        $role = Role::findOrCreate($admin ? 'Quản trị' : 'Biên tập', 'web');
        foreach (['view', 'approve', 'delete'] as $action) {
            $role->givePermissionTo(Permission::findOrCreate(EditorialPermissions::label('post', $action), 'web'));
        }
        $user->assignRole($role);

        return $user;
    }

    private function article(User $user, array $attributes = []): Post
    {
        return Post::withoutEvents(fn (): Post => Post::query()->create(array_merge(['title' => 'Tin kiểm thử', 'slug' => 'tin-'.uniqid(), 'post_type' => 'post', 'status' => 'draft', 'author_id' => $user->id], $attributes)));
    }

    private function media(Post $post): Media
    {
        $media = Media::withoutEvents(fn (): Media => Media::query()->create(['model_type' => Post::class, 'model_id' => $post->id, 'collection_name' => 'avatars', 'name' => 'photo', 'file_name' => 'photo.jpg', 'disk' => 'cms_media', 'conversions_disk' => 'cms_media', 'size' => 4, 'manipulations' => [], 'custom_properties' => [], 'generated_conversions' => [], 'responsive_images' => []]));
        Storage::disk('cms_media')->put($media->id.'/photo.jpg', 'test');
        Storage::disk('cms_media')->put($media->id.'/conversions/photo-thumb.jpg', 'test');

        return $media;
    }

    public function test_permanent_deletion_removes_owned_files_and_related_records(): void
    {
        $user = $this->actor();
        $post = $this->article($user);
        $media = $this->media($post);
        Storage::disk('cms_media')->put('inline/own.jpg', 'inline');
        Post::withoutEvents(fn () => $post->update(['content' => '<img src="/media/cms/inline/own.jpg">']));
        foreach (['cms_editorial_history', 'comments', 'feedback'] as $table) {
            DB::table($table)->insert(['post_id' => $post->id]);
        }
        $result = app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
        $this->assertSame(3, $result['removed']);
        $this->assertFalse(Post::query()->whereKey($post->id)->exists());
        $this->assertFalse(Media::query()->whereKey($media->id)->exists());
        Storage::disk('cms_media')->assertMissing([$media->id.'/photo.jpg', $media->id.'/conversions/photo-thumb.jpg', 'inline/own.jpg']);
        foreach (['cms_editorial_history', 'comments', 'feedback'] as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    public function test_shared_image_and_file_referenced_by_settings_are_kept(): void
    {
        $user = $this->actor();
        $post = $this->article($user);
        $media = $this->media($post);
        $this->article($user, ['content' => '<img src="https://old.example/media/cms/'.$media->id.'/photo.jpg?x=1">']);
        DB::table('options')->insert(['key' => 'shared_thumb', 'value' => '/media/cms/'.$media->id.'/conversions/photo-thumb.jpg']);
        $result = app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
        $this->assertSame(2, $result['shared']);
        $this->assertSame(0, $result['removed']);
        Storage::disk('cms_media')->assertExists([$media->id.'/photo.jpg', $media->id.'/conversions/photo-thumb.jpg']);
    }

    public function test_non_administrator_cannot_permanently_delete(): void
    {
        $user = $this->actor(false);
        $post = $this->article($user);
        $media = $this->media($post);
        try {
            app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
            $this->fail('Deletion should be forbidden');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertTrue(Post::query()->whereKey($post->id)->exists());
        Storage::disk('cms_media')->assertExists($media->id.'/photo.jpg');
    }

    public function test_stale_revision_does_not_delete_anything(): void
    {
        $user = $this->actor();
        $post = $this->article($user);
        $media = $this->media($post);
        DB::table('cms_editorial_meta')->insert(['post_id' => $post->id, 'revision' => 2]);
        try {
            app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
            $this->fail('Stale revision must be rejected');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertTrue(Post::query()->whereKey($post->id)->exists());
        Storage::disk('cms_media')->assertExists($media->id.'/photo.jpg');
    }

    public function test_published_article_must_be_stopped_before_permanent_deletion(): void
    {
        $user = $this->actor();
        $post = $this->article($user, ['status' => 'published']);
        try {
            app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
            $this->fail('Published article must be protected');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertTrue(Post::query()->whereKey($post->id)->exists());
    }

    public function test_linked_article_is_protected_and_trashed_article_can_be_purged(): void
    {
        $user = $this->actor();
        $post = $this->article($user, ['deleted_at' => now()]);
        DB::table('menu_items')->insert(['type' => 'post', 'ref_id' => $post->id]);
        try {
            app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
            $this->fail('Menu reference must be protected');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('confirmation', $error->errors());
        }
        $this->assertTrue(Post::query()->whereKey($post->id)->exists());
        DB::table('menu_items')->delete();
        app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
        $this->assertFalse(Post::query()->whereKey($post->id)->exists());
    }

    public function test_paths_outside_uploads_and_encoded_traversal_are_rejected(): void
    {
        $uploads = app(UploadReferences::class);
        $this->assertNull($uploads->localFile(base_path('artisan')));
        $this->assertNull($uploads->fromUrl('/media/cms/%2e%2e/%2e%2e/artisan'));
        $this->assertNull($uploads->fromUrl('/media/cms/%5c..%5cartisan'));
        $this->assertSame([], $uploads->inText('<img src="/media/cms/%2e%2e/%2e%2e/artisan">'));
    }

    public function test_database_guard_prevents_deletion_on_the_original_database(): void
    {
        $user = $this->actor();
        $post = $this->article($user);
        config(['cmsbackend.editorial_database' => 'g2b_cms_khuyennongcantho']);
        try {
            app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
            $this->fail('Database guard must stop deletion');
        } catch (HttpException $error) {
            $this->assertSame(503, $error->getStatusCode());
        }
        $this->assertTrue(Post::query()->whereKey($post->id)->exists());
    }

    public function test_inline_css_and_json_references_protect_shared_files(): void
    {
        $user = $this->actor();
        $post = $this->article($user);
        $media = $this->media($post);
        $this->article($user, ['content' => '<div style="background-image:url(/media/cms/'.$media->id.'/photo.jpg)"></div>', 'excerpt' => json_encode(['image' => '/media/cms/'.$media->id.'/conversions/photo-thumb.jpg'])]);
        $result = app(PostPurger::class)->execute($user, 'post', (string) $post->id, 0);
        $this->assertSame(2, $result['shared']);
        Storage::disk('cms_media')->assertExists([$media->id.'/photo.jpg', $media->id.'/conversions/photo-thumb.jpg']);
    }

    public function test_confirmation_and_revision_are_required(): void
    {
        $request = new \Modules\CmsBackend\Http\Requests\PermanentlyDeletePostRequest;
        $validator = \Illuminate\Support\Facades\Validator::make([], $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('confirmation', $validator->errors()->toArray());
        $this->assertArrayHasKey('revision', $validator->errors()->toArray());
        $valid = \Illuminate\Support\Facades\Validator::make(['confirmation' => 1, 'revision' => 0], $request->rules(), $request->messages());
        $this->assertFalse($valid->fails());
    }
}
