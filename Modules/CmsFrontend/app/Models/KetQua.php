<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;

/** Câu trả lời thực tế (ks_ket_qua): một dòng cho mỗi giá trị chọn / mỗi câu văn bản. */
class KetQua extends Model
{
    const CREATED_AT = 'kskq_created_at';
    const UPDATED_AT = null;

    protected $table = 'ks_ket_qua';
    protected $primaryKey = 'kskq_id';

    protected $fillable = ['ks_id', 'ch_id', 'ksu_id', 'trl_id', 'kskq_trl_value', 'kskq_van_ban'];

    public function cauHoi()
    {
        return $this->belongsTo(CauHoi::class, 'ch_id', 'ch_id')->withTrashed();
    }

    public function luot()
    {
        return $this->belongsTo(KhaoSatUser::class, 'ksu_id', 'ksu_id');
    }

    public function luaChon()
    {
        return $this->belongsTo(TraLoi::class, 'trl_id', 'trl_id')->withTrashed();
    }
}
