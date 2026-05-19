<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadgeThreeStars extends Model
{
    protected $fillable = [
        'user_id',
        'refer_users',
        'countStatus',
    ];

    protected $casts = [
        'refer_users' => 'array',
    ];

    /**
     * Get the user that owns the badge.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
