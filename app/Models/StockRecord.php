<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRecord extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'stockItemSettingId',
        'itemType',
        'itemAmount',
        'itemMassWeightKG',
        'itemLiquidWeightML',
        'quantity',
        'itemAmountTotal',
        'itemMassWeightKGTotal',
        'itemLiquidWeightMLTotal',
        'activeStatus',
        'deleteStatus'
    ];

    protected $casts = [
        'itemAmount' => 'decimal:2',
        'itemMassWeightKG' => 'integer',
        'itemLiquidWeightML' => 'integer',
        'quantity' => 'integer',
        'itemAmountTotal' => 'decimal:2',
        'itemMassWeightKGTotal' => 'integer',
        'itemLiquidWeightMLTotal' => 'integer',
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

    // Relationship with stock item setting
    public function stockItemSetting(): BelongsTo
    {
        return $this->belongsTo(StockItemSetting::class, 'stockItemSettingId');
    }

    // Scope for active stock records
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    // Scope for non-deleted stock records
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

    // Mutator to automatically calculate totals when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($stockRecord) {
            // Calculate totals if not already set
            if ($stockRecord->itemAmount && $stockRecord->quantity) {
                $stockRecord->itemAmountTotal = $stockRecord->itemAmount * $stockRecord->quantity;
            }
            if ($stockRecord->itemMassWeightKG && $stockRecord->quantity) {
                $stockRecord->itemMassWeightKGTotal = $stockRecord->itemMassWeightKG * $stockRecord->quantity;
            }
            if ($stockRecord->itemLiquidWeightML && $stockRecord->quantity) {
                $stockRecord->itemLiquidWeightMLTotal = $stockRecord->itemLiquidWeightML * $stockRecord->quantity;
            }
        });
    }
}
