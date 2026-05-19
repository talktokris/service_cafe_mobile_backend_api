<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageOffer extends Model
{
    protected $table = 'package_offers';

    protected $fillable = [
        'package_name',
        'package_amount',
        'valid_from_date',
        'valid_to_date',
        'status',
    ];

    protected $casts = [
        'valid_from_date' => 'date',
        'valid_to_date' => 'date',
        'package_amount' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the status text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            0 => 'Not Active',
            1 => 'Active',
            default => 'Unknown'
        };
    }

    /**
     * Get the status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            0 => 'badge-warning',
            1 => 'badge-success',
            default => 'badge-secondary'
        };
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for packages valid in date range
     */
    public function scopeValidInRange($query, $date = null)
    {
        $date = $date ?? now()->toDateString();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from_date')
              ->orWhere('valid_from_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to_date')
              ->orWhere('valid_to_date', '>=', $date);
        });
    }
}