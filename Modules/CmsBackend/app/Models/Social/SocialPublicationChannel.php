<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Models\Social;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;

/** One delivery of a publication to one channel. error_message is shown to users: it must never contain a secret. */
class SocialPublicationChannel extends Model
{
    protected $table = 'social_publication_channels';

    protected $fillable = [
        'social_publication_id', 'channel', 'social_account_id', 'status', 'remote_post_id', 'remote_url',
        'error_code', 'error_message', 'attempts', 'published_at', 'removed_at', 'last_synced_at',
    ];

    protected $casts = [
        'channel' => SocialChannel::class,
        'status' => SocialChannelStatus::class,
        'attempts' => 'integer',
        'published_at' => 'datetime',
        'removed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(SocialPublication::class, 'social_publication_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }
}
