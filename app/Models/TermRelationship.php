<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TermRelationship extends Model
{
    use HasFactory;

    protected $table = 'term_relationships';

    public function object()
    {
        return $this->morphTo();
    }
    public function post()
    {
        return $this->belongsTo(Post::class, 'object_id');
    }
    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class, 'term_taxonomy_id');
    }
}
