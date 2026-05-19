<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = Transaction::with('order:id,orderShortName')
            ->where('transaction_to_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('transaction_id')) {
            $query->where('id', 'like', '%'.$request->transaction_id.'%');
        }
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $transactions = $query->limit(200)->get();

        $totalDebits = 0;
        $totalCredits = 0;
        $runningBalance = 0;

        $withBalance = $transactions->map(function ($transaction) use (&$runningBalance, &$totalDebits, &$totalCredits) {
            if ($transaction->debit_credit == 1) {
                $runningBalance -= $transaction->amount;
                $totalDebits += $transaction->amount;
            } elseif ($transaction->debit_credit == 2) {
                $runningBalance += $transaction->amount;
                $totalCredits += $transaction->amount;
            }
            $transaction->balance = $runningBalance;

            return $transaction;
        });

        return $this->success([
            'transactions' => $withBalance,
            'summary' => [
                'total_debits' => (float) $totalDebits,
                'total_credits' => (float) $totalCredits,
                'balance' => (float) $runningBalance,
            ],
            'wallet_balance' => $user->getCurrentWalletBalance(),
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'transaction_id' => $request->get('transaction_id'),
            ],
        ]);
    }
}
