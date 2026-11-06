<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lượt tham gia khảo sát (ks_khao_sat_users). Giữ nguyên các cột nghề nghiệp cũ (ksu_ket_qua, school_*), khảo sát chung không dùng.
 */
class KhaoSatUser extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'ksu_created_at';
    const UPDATED_AT = 'ksu_updated_at';
    const DELETED_AT = 'ksu_deleted_at';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'ks_khao_sat_users';
    protected $primaryKey = 'ksu_id';

    protected $fillable = [
        'ks_id', 'ksu_ma', 'ksu_trang_thai', 'ksu_hoan_thanh_at', 'ksu_thoi_luong', 'ksu_an_danh',
        'ksu_ho_ten', 'ksu_so_dien_thoai', 'ksu_email', 'ksu_dau_vet', 'ksu_ip_hash', 'ksu_user_agent',
        'ksu_ket_qua', 'school_key', 'school_name',
    ];

    protected $casts = [
        'ksu_an_danh' => 'boolean',
        'ksu_hoan_thanh_at' => 'datetime',
        'ksu_thoi_luong' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'ksu_id';
    }

    public function isCompleted(): bool
    {
        return $this->ksu_trang_thai === self::STATUS_COMPLETED;
    }

    public function khaoSat()
    {
        return $this->belongsTo(KhaoSat::class, 'ks_id', 'ks_id')->withTrashed();
    }

    public function ketQua()
    {
        return $this->hasMany(KetQua::class, 'ksu_id', 'ksu_id');
    }
}
