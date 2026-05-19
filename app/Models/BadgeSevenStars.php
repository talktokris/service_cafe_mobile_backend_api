<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadgeSevenStars extends Model
{
    protected $fillable = [
        'user_id',
        'refer_five_stars',
        'countStatus',
    ];

    protected $casts = [
        'refer_five_stars' => 'array',
    ];

    /**
     * Get the user that owns the badge.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
