<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model implements Translatable
{
    use HasFactory;
    use HasTranslations;
    use \App\Models\Concerns\LocalizedForPublic;

    public function publicOverlayMap(): array
    {
        return ['title' => 'title'];
    }

    /**
     * Title from the language. The address is replaced only for a plain link ("url" type) that has its own address in the
     * language (flag `url_localized`); post/page/category links are resolved from the linked object instead.
     */
    public function applyPublicTranslation(?\Illuminate\Database\Eloquent\Model $translation): void
    {
        $this->setAttribute('title', (string) ($translation?->getAttribute('title') ?? ''));
        $custom = trim((string) ($translation?->getAttribute('custom_url') ?? ''));
        $this->setAttribute('url_localized', $this->getRawOriginal('type') === 'url' && $custom !== '');
        if ($this->getRawOriginal('type') === 'url' && $custom !== '') {
            $this->setAttribute('url', $custom);
        }
    }

    public function translationModel(): string
    {
        return MenuItemTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'menu_item_id';
    }

    public function translatableFields(): array
    {
        return ['title', 'custom_url'];
    }

    public function translationRequiredField(): string
    {
        return 'title';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'menu_id',
      'parent_id',
      'treepath',
      'title',
      'type',
      'url',
      'ref_id',
      'target',
      'icon',
      'feature_key',
      'feature_target',
      'ordinal'
    ];
}
