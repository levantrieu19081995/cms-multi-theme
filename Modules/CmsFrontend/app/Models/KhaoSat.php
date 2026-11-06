<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CmsBackend\Enums\SurveyStatus;

/**
 * Khảo sát (ks_khao_sat). Không có project_key/theme_key: dữ liệu không phụ thuộc theme.
 */
class KhaoSat extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'ks_created_at';
    const UPDATED_AT = 'ks_updated_at';
    const DELETED_AT = 'ks_deleted_at';

    protected $table = 'ks_khao_sat';
    protected $primaryKey = 'ks_id';

    protected $fillable = [
        'ks_ten', 'ks_ma', 'ks_mo_ta_ngan', 'ks_gioi_thieu', 'ks_loai', 'ks_status', 'ks_trang_thai',
        'ks_bat_dau', 'ks_ket_thuc', 'ks_cho_phep_an_danh', 'ks_yeu_cau_thong_tin', 'ks_so_lan_gui',
        'ks_noi_dung_hoan_thanh', 'ks_ghichu', 'ks_created_by', 'ks_updated_by',
    ];

    protected $casts = [
        'ks_bat_dau' => 'datetime',
        'ks_ket_thuc' => 'datetime',
        'ks_cho_phep_an_danh' => 'boolean',
        'ks_yeu_cau_thong_tin' => 'boolean',
        'ks_trang_thai' => 'boolean',
        'ks_so_lan_gui' => 'integer',
    ];

    public function status(): SurveyStatus
    {
        return SurveyStatus::fromValue($this->ks_status);
    }

    /** Đang nhận phản hồi: trạng thái Đang mở và trong khoảng thời gian (nếu có). */
    public function isAcceptingResponses(?\DateTimeInterface $at = null): bool
    {
        $at ??= now();

        return $this->status() === SurveyStatus::Open
            && ($this->ks_bat_dau === null || $this->ks_bat_dau <= $at)
            && ($this->ks_ket_thuc === null || $this->ks_ket_thuc >= $at);
    }

    public function nhom()
    {
        return $this->hasMany(KhaoSatNhom::class, 'ks_id', 'ks_id')->orderBy('nhom_thu_tu')->orderBy('nhom_id');
    }

    public function cauHoi()
    {
        return $this->hasMany(CauHoi::class, 'ks_id', 'ks_id')->orderBy('ch_thu_tu')->orderBy('ch_id');
    }

    public function luotThamGia()
    {
        return $this->hasMany(KhaoSatUser::class, 'ks_id', 'ks_id');
    }

    public function getRouteKeyName(): string
    {
        return 'ks_id';
    }
}
