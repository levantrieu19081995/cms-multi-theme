<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Ngành/nghề gợi ý do trường tự khai báo (ks_career_major), gắn mã RIASEC 1–3 chữ cái. */
class CareerMajor extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'cm_created_at';

    const UPDATED_AT = 'cm_updated_at';

    const DELETED_AT = 'cm_deleted_at';

    protected $table = 'ks_career_major';

    protected $primaryKey = 'cm_id';

    protected $fillable = ['cm_ten', 'cm_ma_riasec', 'cm_mo_ta', 'cm_url', 'cm_thu_tu', 'cm_trang_thai', 'cm_created_by'];

    protected $casts = ['cm_trang_thai' => 'boolean', 'cm_thu_tu' => 'integer'];
}
