<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostTranslation extends Model
{
    protected $table = 'post_translations';

    protected $fillable = ['post_id', 'locale', 'title', 'slug', 'excerpt', 'content', 'seo_title', 'seo_description'];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
