<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class OrderItem extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'tableId',
        'orderId',
        'menuId',
        'quantity',
        'buyingPrice',
        'sellingPrice',
        'taxAmount',
        'adminProfitAmount',
        'adminNetProfitAmount',
        'userCommissionAmount',
        'subTotalAmount',
        'deleteStatus'
    ];

    protected $casts = [
        'buyingPrice' => 'decimal:2',
        'sellingPrice' => 'decimal:2',
        'taxAmount' => 'decimal:2',
        'adminProfitAmount' => 'decimal:2',
        'adminNetProfitAmount' => 'decimal:2',
        'userCommissionAmount' => 'decimal:2',
        'subTotalAmount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            if (empty($orderItem->createUserId) && Auth::check()) {
                $orderItem->createUserId = Auth::id();
            }
        });
    }

    // Relationships
    public function headOffice(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'headOfficeId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'branchId');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createUserId');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'tableId');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderId');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menuId');
    }
}
