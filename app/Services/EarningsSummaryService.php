<?php

namespace App\Services;

use App\Models\Earning;
use Carbon\Carbon;

class EarningsSummaryService
{
    public static function calculate(int $userId): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $totalEarnings = Earning::where('user_id', $userId)
            ->where('transation_type', 1)
            ->where('debit_credit', 2)
            ->sum('ammout');

        $monthEarnings = Earning::where('user_id', $userId)
            ->where('transation_type', 1)
            ->where('debit_credit', 2)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('ammout');

        $totalWithdrawals = Earning::where('user_id', $userId)
            ->where('transation_type', 2)
            ->where('debit_credit', 1)
            ->sum('ammout');

        $monthWithdrawals = Earning::where('user_id', $userId)
            ->where('transation_type', 2)
            ->where('debit_credit', 1)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('ammout');

        $totalRedistributions = Earning::where('user_id', $userId)
            ->where('transation_type', 3)
            ->sum('ammout');

        $monthRedistributions = Earning::where('user_id', $userId)
            ->where('transation_type', 3)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('ammout');

        return [
            'total_earnings' => (float) $totalEarnings,
            'month_earnings' => (float) $monthEarnings,
            'total_withdrawals' => (float) $totalWithdrawals,
            'month_withdrawals' => (float) $monthWithdrawals,
            'total_redistributions' => (float) $totalRedistributions,
            'month_redistributions' => (float) $monthRedistributions,
            'earning_balance' => (float) ($totalEarnings - $totalWithdrawals),
            'min_withdrawal' => (float) env('WITHDRAWAL_MIN_AMOUT', 1000),
        ];
    }
}
