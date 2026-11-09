<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;

/** Tên/mô tả do quản trị chỉnh cho một nhóm RIASEC (ks_career_group). Khóa là chữ cái nhóm. */
class CareerGroup extends Model
{
    const CREATED_AT = null;

    const UPDATED_AT = 'cg_updated_at';

    protected $table = 'ks_career_group';

    protected $primaryKey = 'cg_ma';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['cg_ma', 'cg_ten', 'cg_mo_ta'];
}
