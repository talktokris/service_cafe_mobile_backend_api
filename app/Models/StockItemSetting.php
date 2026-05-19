<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItemSetting extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'itemName',
        'itemType',
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

    // Relationship with stock records
    public function stockRecords(): HasMany
    {
        return $this->hasMany(StockRecord::class, 'stockItemSettingId');
    }

    // Scope for active stock item settings
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    // Scope for non-deleted stock item settings
    public function scopeNotDeleted($query)
    {
        return $query->where('deleteStatus', 0);
    }

    // Scope for food items
    public function scopeFood($query)
    {
        return $query->where('itemType', 'food');
    }

    // Scope for drink items
    public function scopeDrink($query)
    {
        return $query->where('itemType', 'drink');
    }
}
