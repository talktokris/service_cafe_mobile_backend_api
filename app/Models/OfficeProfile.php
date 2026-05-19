<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeProfile extends Model
{
    protected $fillable = [
        'companyName',
        'profileType',
        'regNo',
        'address',
        'phoneNo',
        'contactFirstName',
        'contactLastName',
        'contactEmail',
        'contactMobileNo',
        'activeStatus',
        'deleteStatus'
    ];

    protected $casts = [
        'activeStatus' => 'integer',
        'deleteStatus' => 'integer'
    ];

    // Relationship with users (head office)
    public function headOfficeUsers(): HasMany
    {
        return $this->hasMany(User::class, 'headOfficeId');
    }

    // Relationship with users (branch)
    public function branchUsers(): HasMany
    {
        return $this->hasMany(User::class, 'branchId');
    }

    // Relationship with menu items (head office)
    public function headOfficeMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'headOfficeId');
    }

    // Relationship with menu items (branch)
    public function branchMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'branchId');
    }

    // Scope for active profiles
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    // Scope for non-deleted profiles
    public function scopeNotDeleted($query)
    {
        return $query->where('deleteStatus', 0);
    }
}
