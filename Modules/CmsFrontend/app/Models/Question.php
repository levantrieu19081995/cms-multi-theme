<?php

namespace Modules\CmsFrontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Question extends Model
{
    // protected $table = "questions";
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'title',
        'content',
        'answer',
        'answered_by',
        'answered_at',
        'is_approved'
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_approved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }
}
