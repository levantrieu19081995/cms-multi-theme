<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Liên hệ – góp ý gửi từ website. Không gắn theme/project_key. `address` là địa chỉ/đơn vị, bản ghi cũ có thể trống. */
class LienHe extends Model
{
    use SoftDeletes;

    protected $table = 'lienhe';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'subject',
        'message',
    ];
}
