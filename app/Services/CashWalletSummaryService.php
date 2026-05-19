<?php

namespace App\Services;

use App\Models\CashWalletTransaction;

class CashWalletSummaryService
{
    public static function calculate(int $userId): array
    {
        $cashIn = CashWalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 1)
            ->where('debit_credit', 2)
            ->sum('amount');

        $cashOut = CashWalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 3)
            ->sum('amount');

        $transfer = CashWalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 4)
            ->sum('amount');

        return [
            'total_cash_in' => (float) $cashIn,
            'total_cash_out' => (float) $cashOut,
            'total_transfer' => (float) $transfer,
            'total_balance' => (float) ($cashIn - ($cashOut + $transfer)),
        ];
    }
}
