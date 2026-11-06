<?php

namespace Modules\CmsBackend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuongTieuBieu extends Model implements HasMedia
{
    use \Modules\CmsBackend\Support\UsesSourceMedia;
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'guong_tieu_bieu';

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'show_home_page',
        'rating',
        'role',
    ];


    protected $casts = [
        'show_home_page' => 'integer',
    ];

    /**
     * Public URL key: "<title-slug>-<id>". The id stays last so the page resolves it without a slug column.
     */
    public function getUrlKeyAttribute(): string
    {
        $slug = Str::limit(Str::slug((string) $this->title), 80, '');

        return $slug === '' ? (string) $this->getKey() : rtrim($slug, '-').'-'.$this->getKey();
    }
}
