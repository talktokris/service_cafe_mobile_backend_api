<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankEwalletSetup extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_name',
        'account_type',
        'account_holder_name',
        'account_number',
        'esewa_wallet',
        'khalti_wallet',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
