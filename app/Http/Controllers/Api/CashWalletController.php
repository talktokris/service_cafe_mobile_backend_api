<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashWalletTransaction;
use App\Models\Transaction;
use App\Services\CashOutRequestService;
use App\Services\CashWalletSummaryService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashWalletController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = CashWalletTransaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
            ->orderByDesc('transaction_date');

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        $summary = CashWalletSummaryService::calculate($user->id);
        $balance = (float) $summary['total_balance'];

        return $this->success([
            'transactions' => $query->paginate($request->integer('per_page', 30)),
            'summary' => $summary,
            'daily_remaining' => CashOutRequestService::dailyRemaining($user->id),
            'daily_cash_out_limit' => CashOutRequestService::dailyLimit(),
            'max_withdraw_amount' => CashOutRequestService::maxWithdrawAmount($user->id, $balance),
        ]);
    }

    public function cashOut(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'cash_out_method' => 'required|in:bank_transfer,esewa,khalti',
        ]);

        $user = $request->user();
        $amount = (float) $request->amount;
        $method = $request->cash_out_method;
        $summary = CashWalletSummaryService::calculate($user->id);
        $balance = (float) $summary['total_balance'];

        $setup = $user->bankEwalletSetup;
        $methodCheck = CashOutRequestService::validateMethodSetup($setup, $method);
        if (! $methodCheck['ok']) {
            return $this->error($methodCheck['message'], 422);
        }

        $amountCheck = CashOutRequestService::validateAmount($user->id, $amount, $balance);
        if (! $amountCheck['ok']) {
            return $this->error($amountCheck['message'], 422);
        }

        try {
            $payoutSnapshot = CashOutRequestService::buildPayoutSnapshot($setup, $method);

            CashWalletTransaction::create(array_merge([
                'user_id' => $user->id,
                'trigger_id' => $user->id,
                'create_user_id' => $user->id,
                'name' => 'Amount Cash Out',
                'type' => 'Cash Out',
                'transaction_type' => 3,
                'debit_credit' => 1,
                'amount' => $amount,
                'transaction_date' => now(),
                'cash_out_status' => 0,
                'tax_status' => 0,
                'status' => 1,
                'deleteStatus' => 0,
            ], $payoutSnapshot));

            return $this->success([
                'summary' => CashWalletSummaryService::calculate($user->id),
                'daily_remaining' => CashOutRequestService::dailyRemaining($user->id),
                'daily_cash_out_limit' => CashOutRequestService::dailyLimit(),
                'max_withdraw_amount' => CashOutRequestService::maxWithdrawAmount($user->id, $balance),
            ], 'Cash out request submitted. We will process payment to your selected method.');
        } catch (\Exception $e) {
            Log::error('API cash out failed', ['error' => $e->getMessage()]);

            return $this->error('Failed to submit cash out.', 500);
        }
    }

    public function transfer(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $user = $request->user();
        $amount = (float) $request->amount;
        $summary = CashWalletSummaryService::calculate($user->id);

        if ($amount > $summary['total_balance']) {
            return $this->error('Amount exceeds cash wallet balance.', 422);
        }

        try {
            DB::beginTransaction();
            $today = now();

            CashWalletTransaction::create([
                'user_id' => $user->id,
                'trigger_id' => $user->id,
                'create_user_id' => $user->id,
                'name' => 'Amount Transfer to Purchase Account',
                'type' => 'Cash Transfer',
                'transaction_type' => 4,
                'debit_credit' => 1,
                'amount' => $amount,
                'transaction_date' => $today,
                'status' => 1,
                'deleteStatus' => 0,
            ]);

            Transaction::create([
                'transaction_nature' => 'Tax Amount Debited',
                'transaction_type' => 'Repurchase Credited',
                'debit_credit' => 2,
                'matching_date' => $today->toDateString(),
                'transaction_from_id' => $user->id,
                'transaction_to_id' => $user->id,
                'trigger_id' => $user->id,
                'created_user_id' => $user->id,
                'amount' => $amount,
                'transaction_date' => $today,
                'status' => 1,
                'countStatus' => 0,
            ]);

            DB::commit();

            return $this->success([
                'summary' => CashWalletSummaryService::calculate($user->id),
            ], 'Transferred to purchase wallet successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Transfer failed.', 500);
        }
    }
}
