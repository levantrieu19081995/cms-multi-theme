<?php

namespace Modules\CmsBackend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// use Modules\CmsBackend\Database\Factories\DoiTacFactory;
class DoiTac extends Model implements HasMedia
{
    use \Modules\CmsBackend\Support\UsesSourceMedia;
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'doi_tac';
    protected $fillable = ['title', 'content', 'slug', 'show_home_page', 'excerpt'];
}

