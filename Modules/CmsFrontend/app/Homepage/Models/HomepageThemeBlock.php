<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Vị trí của một instance trên trang chủ của một theme (bố cục là cấu hình trình bày nên gắn theme_key). */
class HomepageThemeBlock extends Model
{
    protected $table = 'homepage_theme_blocks';

    protected $fillable = ['theme_key', 'block_instance_id', 'zone_key', 'variant_key', 'effect_key', 'enabled', 'sort_order'];

    protected $casts = ['enabled' => 'boolean', 'sort_order' => 'integer'];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(HomepageBlockInstance::class, 'block_instance_id');
    }
}
