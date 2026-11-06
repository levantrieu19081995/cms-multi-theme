<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cấu hình NGUỒN NỘI DUNG của một instance trong một theme (chỉ ID/số/chuỗi theo schema; không lưu class, Blade, JS, CSS hay tên effect). */
class HomepageBlockSetting extends Model
{
    protected $table = 'homepage_block_settings';

    protected $fillable = ['theme_key', 'block_instance_id', 'settings'];

    protected $casts = ['settings' => 'array'];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(HomepageBlockInstance::class, 'block_instance_id');
    }
}
