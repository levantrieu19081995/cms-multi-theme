<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Một cụm cụ thể tạo từ một Block Definition (Definition là code, instance là dữ liệu). */
class HomepageBlockInstance extends Model
{
    protected $table = 'homepage_block_instances';

    protected $fillable = ['instance_key', 'definition_key', 'name_vi', 'name_en', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function themeBlocks(): HasMany
    {
        return $this->hasMany(HomepageThemeBlock::class, 'block_instance_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(HomepageBlockSetting::class, 'block_instance_id');
    }
}
