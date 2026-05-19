<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberUplineRank extends Model
{
    protected $table = 'member_upline_rank';

    protected $fillable = [
        'user_id',
        'refferal_user_id',
        'three_star_user_id',
        'five_star_user_id',
        'seven_star_user_id',
        'mega_star_user_id',
        'giga_star_user_id',
    ];

    /**
     * Get the main user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the referral user
     */
    public function referralUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refferal_user_id');
    }

    /**
     * Get the three star user
     */
    public function threeStarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'three_star_user_id');
    }

    /**
     * Get the five star user
     */
    public function fiveStarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'five_star_user_id');
    }

    /**
     * Get the seven star user
     */
    public function sevenStarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seven_star_user_id');
    }

    /**
     * Get the mega star user
     */
    public function megaStarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mega_star_user_id');
    }

    /**
     * Get the giga star user
     */
    public function gigaStarUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'giga_star_user_id');
    }
}
