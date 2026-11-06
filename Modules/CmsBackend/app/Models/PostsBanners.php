<?php

namespace Modules\CmsBackend\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\CmsBackend\Database\Factories\PostsBannersFactory;

class PostsBanners extends Model
{
    use HasFactory;

    protected $table = 'posts_banners';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'post_id',
        'ref_id',
        'redirect',
        'description',
        'btn_text',
        'show_text',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // banner thuộc post nào trong bảng posts
    public function Post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
