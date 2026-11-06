<?php

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Support\EditorialPermissions;
use Modules\CmsBackend\Support\EditorialPermissionHierarchy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InstallEditorialWorkflow extends Command
{
    protected $signature = 'cms:workflow-install';
    protected $description = 'Cài quy trình tin/sự kiện vào database riêng Khuyến nông; không chạy migration/seeder nguồn.';

    public function handle(): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS db')->db;
        if ($database !== config('cmsbackend.editorial_database', 'cms_workspace') || $database === env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang')) {
            $this->error('Database không phải database riêng Khuyến nông. Chưa ghi dữ liệu.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('cms_editorial_meta')) {
            Schema::create('cms_editorial_meta', function (Blueprint $table) {
                $table->string('post_id', 64)->primary();
                $table->unsignedInteger('revision')->default(0);
                $table->unsignedInteger('approved_revision')->nullable();
                $table->string('approved_by', 64)->nullable();
                $table->dateTime('event_starts_at')->nullable();
                $table->dateTime('event_ends_at')->nullable();
                $table->text('related_parties')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('cms_editorial_history')) {
            Schema::create('cms_editorial_history', function (Blueprint $table) {
                $table->id();
                $table->string('post_id', 64)->index();
                $table->string('user_id', 64);
                $table->string('action', 40);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40);
                $table->unsignedInteger('revision');
                $table->text('note')->nullable();
                $table->dateTime('created_at');
            });
        }
        DB::transaction(function () {
            $author = Role::findOrCreate(EditorialPermissions::AUTHOR_ROLE, 'web');
            $editor = Role::findOrCreate(EditorialPermissions::EDITOR_ROLE, 'web');
            foreach (EditorialPermissions::definitions() as $definition) {
                $permission = Permission::findOrCreate($definition['name'], 'web');
                if (Schema::hasColumn('permissions', 'slug')) {
                    $permission->slug = $definition['slug'];
                    $permission->save();
                }
                $editor->givePermissionTo($permission);
                if (in_array($definition['action'], ['view', 'create', 'update'], true)) $author->givePermissionTo($permission);
                $admin = Role::where('name', 'Quản trị')->where('guard_name', 'web')->first();
                if ($admin) $admin->givePermissionTo($permission);
            }
            // Preserve old roles and assignments; add only the CMS equivalents in this database.
            foreach (['Người viết bài' => $author, 'Người duyệt bài' => $editor] as $oldName => $newRole) {
                $oldRole = Role::where('name', $oldName)->where('guard_name', 'web')->first();
                if ($oldRole) foreach ($oldRole->users()->get() as $user) $user->assignRole($newRole);
            }
        });
        EditorialPermissionHierarchy::repair();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Đã cài quy trình, vai trò và quyền riêng tại '.$database.'. Giữ nguyên bài viết và vai trò cũ.');
        return self::SUCCESS;
    }
}
