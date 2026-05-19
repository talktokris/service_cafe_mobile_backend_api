<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'tableShortName',
        'tableShortFullName',
        'activeStatus',
        'deleteStatus'
    ];

    protected $casts = [
        'activeStatus' => 'integer',
        'deleteStatus' => 'integer'
    ];

    // Relationship with head office
    public function headOffice(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'headOfficeId');
    }

    // Relationship with branch
    public function branch(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'branchId');
    }

    // Relationship with creator user
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createUserId');
    }

    // Scope for active tables
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    // Scope for non-deleted tables
    public function scopeNotDeleted($query)
    {
        return $query->where('deleteStatus', 0);
    }
}
