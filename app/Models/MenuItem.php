<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'foodCategoryId',
        'menuName',
        'menuType',
        'drinkAmount',
        'buyingPrice',
        'adminProfitPercentage',
        'adminProfitAmount',
        'userCommissionPercentage',
        'userCommissionAmount',
        'sellingPrice',
        'govTaxPercentage',
        'govTaxAmount',
        'sellingWithTaxPrice',
        'activeStatus',
        'deleteStatus'
    ];

    protected $casts = [
        'drinkAmount' => 'decimal:2',
        'buyingPrice' => 'decimal:2',
        'adminProfitPercentage' => 'decimal:2',
        'adminProfitAmount' => 'decimal:2',
        'userCommissionPercentage' => 'decimal:2',
        'userCommissionAmount' => 'decimal:2',
        'sellingPrice' => 'decimal:2',
        'govTaxPercentage' => 'decimal:2',
        'govTaxAmount' => 'decimal:2',
        'sellingWithTaxPrice' => 'decimal:2',
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

    public function foodCategory(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'foodCategoryId');
    }

    // Scope for active menu items
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    // Scope for non-deleted menu items
    public function scopeNotDeleted($query)
    {
        return $query->where('deleteStatus', 0);
    }

    // Scope for food items
    public function scopeFood($query)
    {
        return $query->where('menuType', 'food');
    }

    // Scope for drink items
    public function scopeDrink($query)
    {
        return $query->where('menuType', 'drink');
    }
}
