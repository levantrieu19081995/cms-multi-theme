<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermTranslation extends Model
{
    protected $table = 'term_translations';

    protected $fillable = ['term_id', 'locale', 'name', 'slug'];

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }
}
