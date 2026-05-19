<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_nature',
        'transaction_type',
        'debit_credit',
        'matching_date',
        'transaction_from_id',
        'transaction_to_id',
        'trigger_id',
        'order_id',
        'created_user_id',
        'amount',
        'transaction_date',
        'status',
        'countStatus',
        'tax_status',
    ];

    protected $casts = [
        'matching_date' => 'date',
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'debit_credit' => 'integer',
        'status' => 'integer',
        'countStatus' => 'integer',
        'tax_status' => 'integer',
    ];

    /**
     * Get the user who initiated the transaction
     */
    public function transactionFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transaction_from_id');
    }

    /**
     * Get the user who received the transaction
     */
    public function transactionTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transaction_to_id');
    }

    /**
     * Get the user who triggered the transaction
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trigger_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /**
     * Get the order associated with the transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get debit credit status as text
     */
    public function getDebitCreditTextAttribute(): string
    {
        return match($this->debit_credit) {
            1 => 'Debit',
            2 => 'Credit',
            default => 'Unknown'
        };
    }

    /**
     * Get status as text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            0 => 'Pending',
            1 => 'Completed',
            2 => 'Failed',
            default => 'Unknown'
        };
    }

    /**
     * Get tax status as text
     */
    public function getTaxStatusTextAttribute(): string
    {
        return match($this->tax_status) {
            0 => 'Not Tax Amount',
            1 => 'Tax Amount',
            default => 'Unknown'
        };
    }
}
