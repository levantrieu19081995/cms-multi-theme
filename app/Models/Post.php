<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\CommentStatus;
use App\Models\Concerns\HasTranslations;
use App\Observers\PostObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([PostObserver::class])]
class Post extends Model implements HasMedia, Translatable
{
    use HasFactory;
    use HasTranslations;
    use \App\Models\Concerns\LocalizedForPublic;

    /** Public pages in another language read these from post_translations (see PublicLocaleScope). */
    public function publicOverlayMap(): array
    {
        return ['title' => 'title', 'slug' => 'slug', 'excerpt' => 'excerpt', 'content' => 'content'];
    }

    public function applyPublicTranslation(?\Illuminate\Database\Eloquent\Model $translation): void
    {
        foreach ($this->publicOverlayMap() as $attribute => $column) {
            $value = $translation?->getAttribute($column);
            $this->setAttribute($attribute, $value === null ? '' : $value);
        }
        // SEO texts of the language (null when none: the page falls back to the excerpt / site text of that language).
        $this->setAttribute('seo_title', $translation?->getAttribute('seo_title'));
        $this->setAttribute('seo_description', $translation?->getAttribute('seo_description'));
    }
    use InteractsWithMedia;

    public function translationModel(): string
    {
        return PostTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'post_id';
    }

    public function translatableFields(): array
    {
        return ['title', 'slug', 'excerpt', 'content', 'seo_title', 'seo_description'];
    }

    public function translationRequiredField(): string
    {
        return 'title';
    }

    // Bài viết thuộc về một tác giả
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Bài viết được xuất bản bởi một người dùng
    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_id');
    }
    // Quan hệ với bình luận

    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'post_id')
            ->whereNull('parent_id') // Chỉ lấy bình luận gốc
            ->where('status', CommentStatus::APPROVED->value);
    }

    // public function taxonomies()
    // {
    //     return $this->belongsToMany(
    //         Taxonomy::class,
    //         'term_relationships',
    //         'object_id',
    //         'term_taxonomy_id'
    //     )->withPivot('term_order');
    // }
    public function taxonomies()
    {
        return $this->morphToMany(
            Taxonomy::class,
            'object',
            'term_relationships',
            'object_id',
            'term_taxonomy_id'
        )->where('term_relationships.object_type', '=', self::class)
            ->withPivot('term_order');
    }
    // Bài viết có avatar
    public function addAvatar($avatar)
    {
        $this->addMedia($avatar)->toMediaCollection('avatars');
    }
    public function parent()
    {
        return $this->belongsTo(Post::class, 'post_parent', 'id');
    }

    public function children()
    {
        return $this->hasMany(Post::class, 'post_parent', 'id');
    }
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'title',
        'content',
        'slug',
        'author_id',
        'publisher_id',
        'status',
        'order',
        'views',
        'publish_type',
        'approved_at',
        'comment_status',
        'post_type',
        'post_parent',
        'show_home_page',
        'rejected_at',
        'format',
        'excerpt',
        'author_names',
        'created_at',
        'updated_at',
        'deleted_at',
        'published_at',
        'publication_date_expires',
        'render_file_in_view'
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y',
        'updated_at' => 'datetime:d/m/Y',
    ];
}
