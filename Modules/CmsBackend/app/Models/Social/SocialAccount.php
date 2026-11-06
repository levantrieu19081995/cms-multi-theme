<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Models\Social;

use Illuminate\Database\Eloquent\Model;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;

/**
 * Account of a social platform for one project. `credentials` is encrypted at rest (APP_KEY) and hidden from every
 * array / JSON conversion; it is never logged. Phase 1 stores nothing here.
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'project_key', 'platform', 'name', 'external_account_id', 'status', 'credentials',
        'token_expires_at', 'last_checked_at', 'connected_by',
    ];

    protected $hidden = ['credentials'];

    protected $casts = [
        'platform' => SocialChannel::class,
        'status' => SocialAccountStatus::class,
        'credentials' => 'encrypted:array',
        'token_expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function isConnected(): bool
    {
        return $this->status === SocialAccountStatus::CONNECTED
            && ($this->token_expires_at === null || $this->token_expires_at->isFuture());
    }

    /** Never dump the encrypted payload through var_dump / logs of the model. */
    public function __debugInfo(): array
    {
        return array_diff_key($this->attributes, ['credentials' => true]);
    }
}
