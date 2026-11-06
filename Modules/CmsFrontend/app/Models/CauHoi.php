<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CmsBackend\Enums\SurveyQuestionType;

/** Câu hỏi (ks_cau_hoi). ch_trang_thai = bật/tắt; ch_type 'radio' (cũ) = một lựa chọn. */
class CauHoi extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'ch_created_at';
    const UPDATED_AT = 'ch_updated_at';
    const DELETED_AT = 'ch_deleted_at';

    protected $table = 'ks_cau_hoi';
    protected $primaryKey = 'ch_id';

    protected $fillable = [
        'ks_id', 'nhom_id', 'ch_ma', 'ch_type', 'ch_noidung', 'ch_mo_ta', 'ch_bat_buoc', 'ch_trang_thai',
        'ch_thu_tu', 'ch_cau_hinh',
    ];

    protected $casts = [
        'ch_bat_buoc' => 'boolean',
        'ch_trang_thai' => 'boolean',
        'ch_cau_hinh' => 'array',
        'ch_thu_tu' => 'integer',
    ];

    public function kieu(): SurveyQuestionType
    {
        return SurveyQuestionType::fromStored($this->ch_type);
    }

    public function khaoSat()
    {
        return $this->belongsTo(KhaoSat::class, 'ks_id', 'ks_id');
    }

    public function nhom()
    {
        return $this->belongsTo(KhaoSatNhom::class, 'nhom_id', 'nhom_id');
    }

    public function traLoi()
    {
        return $this->hasMany(TraLoi::class, 'ch_id', 'ch_id')->orderBy('trl_thu_tu')->orderBy('trl_id');
    }

    public function ketQua()
    {
        return $this->hasMany(KetQua::class, 'ch_id', 'ch_id');
    }
}
