<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\QuanLyXayDung\Models\HoiVien;

class Taxonomy extends Model
{
    use HasFactory;

    protected $table = 'term_taxonomy';

    protected $primaryKey = 'term_taxonomy_id';

    public $timestamps = false;

    protected $fillable = [
        'term_id',
        'taxonomy',
        'term_taxonomy_id',
        'parent',
        'description',
        'loai_chuyen_muc'
    ];

    /**
     * The attributes that are mass assignable.
     */
    // Quan hệ với Terms
    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function parent()
    {
        return $this->belongsTo(Taxonomy::class, 'parent', 'term_taxonomy_id');
    }

    public function children()
    {
        return $this->hasMany(Taxonomy::class, 'parent', 'term_taxonomy_id');
    }

    // public function children()
    // {
    //     return $this->hasMany(Taxonomy::class, 'parent', 'term_taxonomy_id')
    //         ->with('term')
    //         ->with('children');  // đệ quy vô hạn
    // }

    public function posts()
    {
        return $this->belongsToMany(
            Post::class,
            'term_relationships',
            'term_taxonomy_id',
            'object_id'

        );
    }
    public function hoiViens()
    {
        return $this->belongsToMany(
            HoiVien::class,
            'term_relationships',
            'term_taxonomy_id',
            'object_id'

        );
    }
}
