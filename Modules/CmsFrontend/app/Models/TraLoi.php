<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Lựa chọn của một câu hỏi (ks_tra_loi). Không phải câu trả lời của người tham gia — đó là KetQua. */
class TraLoi extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'trl_created_at';
    const UPDATED_AT = 'trl_updated_at';
    const DELETED_AT = 'trl_deleted_at';

    protected $table = 'ks_tra_loi';
    protected $primaryKey = 'trl_id';

    protected $fillable = ['ch_id', 'trl_ma', 'trl_noidung', 'trl_value', 'trl_thu_tu', 'trl_trang_thai'];

    protected $casts = ['trl_trang_thai' => 'boolean', 'trl_thu_tu' => 'integer'];

    public function cauHoi()
    {
        return $this->belongsTo(CauHoi::class, 'ch_id', 'ch_id');
    }
}
