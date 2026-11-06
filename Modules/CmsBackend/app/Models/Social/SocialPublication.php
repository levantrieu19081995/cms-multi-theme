<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Models\Social;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;

/**
 * One preparation of a multi-channel publication. title / message / image_url / public_url are a snapshot taken when it was
 * created: never read them from the CMS post when showing history. The history is never deleted.
 */
class SocialPublication extends Model
{
    protected $table = 'social_publications';

    protected $fillable = [
        'post_id', 'project_key', 'locale', 'title', 'message', 'image_url', 'public_url', 'status',
        'idempotency_key', 'created_by', 'updated_by', 'published_at', 'archived_at',
    ];

    protected $casts = [
        'status' => SocialPublicationStatus::class,
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // A publication that has left draft (or was archived after being prepared) is history: it is archived, never deleted.
        static::deleting(function (self $publication) {
            if ($publication->status !== SocialPublicationStatus::DRAFT) {
                throw new LogicException('A social publication that has left draft cannot be deleted.');
            }
        });
    }

    public function channels(): HasMany
    {
        return $this->hasMany(SocialPublicationChannel::class, 'social_publication_id')->orderBy('id');
    }

    /** The CMS post it was made from; null once the post was permanently deleted (the snapshot stays). */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScopes();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
