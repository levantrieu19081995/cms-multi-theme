<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Nhóm/phần câu hỏi (ks_nhom). */
class KhaoSatNhom extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'nhom_created_at';
    const UPDATED_AT = 'nhom_updated_at';
    const DELETED_AT = 'nhom_deleted_at';

    protected $table = 'ks_nhom';
    protected $primaryKey = 'nhom_id';

    protected $fillable = ['ks_id', 'nhom_ma', 'nhom_ten', 'nhom_mo_ta', 'nhom_thu_tu'];

    public function khaoSat()
    {
        return $this->belongsTo(KhaoSat::class, 'ks_id', 'ks_id');
    }

    public function cauHoi()
    {
        return $this->hasMany(CauHoi::class, 'nhom_id', 'nhom_id')->orderBy('ch_thu_tu')->orderBy('ch_id');
    }
}
