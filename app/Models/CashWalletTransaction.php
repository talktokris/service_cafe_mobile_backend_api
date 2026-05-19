<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashWalletTransaction extends Model
{
    protected $table = 'cash_wallets_transactions';

    protected $fillable = [
        'user_id',
        'trigger_id',
        'name',
        'type',
        'transaction_type',
        'debit_credit',
        'amount',
        'transaction_date',
        'cash_out_status',
        'tax_status',
        'create_user_id',
        'status',
        'deleteStatus',
        'cash_out_reference_no',
        'cash_out_description',
        'cash_out_user_id',
        'cash_out_date',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'transaction_type' => 'integer',
        'debit_credit' => 'integer',
        'cash_out_status' => 'integer',
        'tax_status' => 'integer',
        'status' => 'integer',
        'deleteStatus' => 'integer',
        'cash_out_date' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who created this transaction
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    public function cashOutUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_out_user_id');
    }
}
