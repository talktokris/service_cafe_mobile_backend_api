<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    protected $table = 'earnings';

    protected $fillable = [
        'user_id',
        'order_id', 
        'user_trigger_id',
        'earning_name',
        'earning_type',
        'earning_description',
        'ammout',
        'debit_credit',
        'transation_type',
        'withdrawal_status',
        'redistribution_status',
        'status',
        'deleteStatus',
        'countStatus'
    ];

    protected $casts = [
        'ammout' => 'decimal:2',
        'debit_credit' => 'integer',
        'transation_type' => 'integer',
        'withdrawal_status' => 'integer',
        'redistribution_status' => 'integer',
        'status' => 'integer',
        'deleteStatus' => 'integer', 
        'countStatus' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function triggerUser()
    {
        return $this->belongsTo(User::class, 'user_trigger_id');
    }
}
