<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\CmsBackend\Services\Developer\PermissionAvailability;
use Modules\QuanLyXayDung\Models\DanhGiaHoiVien;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
  use HasFactory, Notifiable, HasRoles, SoftDeletes, HasPermissions {
    HasPermissions::hasPermissionTo as private spatieHasPermissionTo;
    HasPermissions::getAllPermissions as private spatieGetAllPermissions;
  }

  /**
   * Every permission check (can(), @can, middleware, hasAnyPermission, ...) ends here, so a permission the Developer has not
   * made available to this deployment is refused first, whatever role the user has (including "Quản trị").
   */
  public function hasPermissionTo($permission, $guardName = null): bool
  {
    if (! app(PermissionAvailability::class)->isAvailable($permission)) {
      return false;
    }

    return $this->spatieHasPermissionTo($permission, $guardName);
  }

  /** Permissions in force: those the Developer made available (used by the menu filter). */
  public function getAllPermissions(): \Illuminate\Support\Collection
  {
    return app(PermissionAvailability::class)->filter($this->spatieGetAllPermissions());
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'status',
    'password',
    'type',
    'username'
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  // Một user có thể là tác giả của nhiều bài viết
  public function authoredPosts()
  {
      return $this->hasMany(Post::class, 'author_id');
  }
  // Một user có thể là người xuất bản nhiều bài viết
  public function publishedPosts()
  {
      return $this->hasMany(Post::class, 'published_id',);
  }
  public function danhGia(){
    return $this->hasMany(DanhGiaHoiVien::class,'user_id');
}
}
