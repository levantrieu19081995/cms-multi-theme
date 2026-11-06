<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model implements Translatable
{
    use HasFactory;
    use HasTranslations;
    use \App\Models\Concerns\LocalizedForPublic;

    public function publicOverlayMap(): array
    {
        return ['name' => 'name', 'slug' => 'slug'];
    }

    /** Categories are shared structure: one without a name in the language stays reachable by code, but shows blank names (never Vietnamese). */
    public function publicRequiresTranslation(): bool
    {
        return false;
    }

    public function translationModel(): string
    {
        return TermTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'term_id';
    }

    public function translatableFields(): array
    {
        return ['name', 'slug'];
    }

    public function translationRequiredField(): string
    {
        return 'name';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'term_id',
        'name',
        'slug',
    ];
    protected $primaryKey = 'term_id';

    public $timestamps = false;

    public function taxonomy()
    {
        return $this->hasOne(Taxonomy::class, 'term_id');
    }
}
