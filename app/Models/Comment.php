<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'content', 'status', 'parent_id'];
    //public $timestamps = false;
    // Quan hệ với bài viết
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    // Quan hệ với người dùng
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // Quan hệ với bình luận con
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
                    ->where('status', 'approved'); // Chỉ lấy bình luận đã được phê duyệt
    }

    // Quan hệ với bình luận cha
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    protected $casts = [
        'created_at' => 'datetime:d/m/Y',
        'updated_at' => 'datetime:d/m/Y',
    ];
}
