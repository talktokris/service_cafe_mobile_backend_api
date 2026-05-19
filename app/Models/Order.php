<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'tableId',
        'tableOccupiedStatus',
        'buyingPrice',
        'sellingPrice',
        'discountAmount',
        'taxAmount',
        'adminProfitAmount',
        'adminNetProfitAmount',
        'userCommissionAmount',
        'customerType',
        'memberUserId',
        'txn_otp',
        'free_paid_member_status',
        'leadership_status',
        'chaque_match_status',
        'tax_status',
        'orderStaredDateTime',
        'orderEndDateTime',
        'paymentType',
        'paymentReference',
        'otp_code',
        'otp_sent_at',
        'otp_verified_at',
        'otp_status',
        'otp_phone',
        'otp_email',
        'notes',
        'paymentStatus',
        'deleteStatus'
    ];

    protected $casts = [
        'buyingPrice' => 'decimal:2',
        'sellingPrice' => 'decimal:2',
        'discountAmount' => 'decimal:2',
        'taxAmount' => 'decimal:2',
        'adminProfitAmount' => 'decimal:2',
        'adminNetProfitAmount' => 'decimal:2',
        'userCommissionAmount' => 'decimal:2',
        'free_paid_member_status' => 'integer',
        'leadership_status' => 'integer',
        'chaque_match_status' => 'integer',
        'tax_status' => 'integer',
        'orderStaredDateTime' => 'datetime',
        'orderEndDateTime' => 'datetime',
        'otp_sent_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($order) {
            if (empty($order->createUserId) && Auth::check()) {
                $order->createUserId = Auth::id();
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

    public function memberUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'memberUserId');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'orderId');
    }

    /**
     * Get all transactions associated with this order
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'order_id');
    }

    /**
     * Get member status as text
     */
    public function getMemberStatusTextAttribute(): string
    {
        return match($this->free_paid_member_status) {
            0 => 'Free Member',
            1 => 'Paid Member',
            default => 'Unknown'
        };
    }

    /**
     * Check if order is for a free member
     */
    public function isFreeMember(): bool
    {
        return $this->free_paid_member_status === 0;
    }

    /**
     * Check if order is for a paid member
     */
    public function isPaidMember(): bool
    {
        return $this->free_paid_member_status === 1;
    }

    /**
     * Check if leadership status is done
     */
    public function isLeadershipStatusDone(): bool
    {
        return $this->leadership_status === 1;
    }

    /**
     * Mark leadership status as done
     */
    public function markLeadershipStatusDone(): void
    {
        $this->update(['leadership_status' => 1]);
    }

    /**
     * Check if chaque match status is done
     */
    public function isChaqueMatchStatusDone(): bool
    {
        return $this->chaque_match_status === 1;
    }

    /**
     * Mark chaque match status as done
     */
    public function markChaqueMatchStatusDone(): void
    {
        $this->update(['chaque_match_status' => 1]);
    }

    /**
     * Check if tax status is done
     */
    public function isTaxStatusDone(): bool
    {
        return $this->tax_status === 1;
    }

    /**
     * Mark tax status as done
     */
    public function markTaxStatusDone(): void
    {
        $this->update(['tax_status' => 1]);
    }

    /**
     * Check if all statuses are done
     */
    public function areAllStatusesDone(): bool
    {
        return $this->isLeadershipStatusDone() && 
               $this->isChaqueMatchStatusDone() && 
               $this->isTaxStatusDone();
    }

    /**
     * Get status summary as array
     */
    public function getStatusSummary(): array
    {
        return [
            'leadership_status' => [
                'value' => $this->leadership_status,
                'is_done' => $this->isLeadershipStatusDone(),
                'text' => $this->isLeadershipStatusDone() ? 'Done' : 'Not Done'
            ],
            'chaque_match_status' => [
                'value' => $this->chaque_match_status,
                'is_done' => $this->isChaqueMatchStatusDone(),
                'text' => $this->isChaqueMatchStatusDone() ? 'Done' : 'Not Done'
            ],
            'tax_status' => [
                'value' => $this->tax_status,
                'is_done' => $this->isTaxStatusDone(),
                'text' => $this->isTaxStatusDone() ? 'Done' : 'Not Done'
            ]
        ];
    }
}
