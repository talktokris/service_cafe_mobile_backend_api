<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashWalletTransaction;
use App\Models\Earning;
use App\Models\Transaction;
use App\Services\EarningsSummaryService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EarningsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = Earning::where('user_id', $user->id)
            ->whereBetween('created_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('transation_type', $request->type);
        }

        return $this->success([
            'earnings' => $query->paginate($request->integer('per_page', 30)),
            'summary' => EarningsSummaryService::calculate($user->id),
        ]);
    }

    public function withdraw(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $user = $request->user();
        $amount = (float) $request->amount;
        $minWithdrawal = (float) env('WITHDRAWAL_MIN_AMOUT', 1000);
        $summary = EarningsSummaryService::calculate($user->id);

        if ($amount < $minWithdrawal) {
            return $this->error("Minimum withdrawal amount is NRS {$minWithdrawal}.", 422);
        }
        if ($amount > $summary['earning_balance']) {
            return $this->error('Withdrawal amount cannot exceed your earning balance.', 422);
        }

        try {
            DB::beginTransaction();

            $personalTaxPercentage = (float) env('PERSONAL_TAX_PERCENTAGE', 15);
            $cashoutWithdrawalAmount = $amount * 0.80;
            $redistributionWithdrawalAmount = $amount * 0.20;
            $cashInAmount = $amount * 0.80;
            $taxAmount = $cashInAmount * ($personalTaxPercentage / 100);
            $today = now();

            Earning::create([
                'user_id' => $user->id,
                'user_trigger_id' => $user->id,
                'earning_name' => 'Cash Out',
                'earning_type' => 'Withdrawal',
                'earning_description' => 'Cash Out Withdrawal',
                'ammout' => $cashoutWithdrawalAmount,
                'debit_credit' => 1,
                'transation_type' => 2,
                'withdrawal_status' => 1,
                'redistribution_status' => 0,
                'status' => 1,
                'deleteStatus' => 0,
                'countStatus' => 1,
            ]);

            Earning::create([
                'user_id' => $user->id,
                'user_trigger_id' => $user->id,
                'earning_name' => 'Redistribution',
                'earning_type' => 'Withdrawal',
                'earning_description' => 'Redistribution Withdrawal',
                'ammout' => $redistributionWithdrawalAmount,
                'debit_credit' => 1,
                'transation_type' => 2,
                'withdrawal_status' => 1,
                'redistribution_status' => 0,
                'status' => 1,
                'deleteStatus' => 0,
                'countStatus' => 1,
            ]);

            CashWalletTransaction::create([
                'user_id' => $user->id,
                'trigger_id' => $user->id,
                'create_user_id' => $user->id,
                'name' => 'Cash In from Withdrawal',
                'type' => 'Cash In',
                'transaction_type' => 1,
                'debit_credit' => 2,
                'amount' => $cashInAmount,
                'transaction_date' => $today,
                'status' => 1,
                'deleteStatus' => 0,
            ]);

            if ($taxAmount > 0) {
                CashWalletTransaction::create([
                    'user_id' => $user->id,
                    'trigger_id' => $user->id,
                    'create_user_id' => $user->id,
                    'name' => 'Tax Amount',
                    'type' => 'Tax',
                    'transaction_type' => 2,
                    'debit_credit' => 1,
                    'amount' => $taxAmount,
                    'transaction_date' => $today,
                    'status' => 1,
                    'deleteStatus' => 0,
                ]);
            }

            Transaction::create([
                'transaction_nature' => 'Repurchase Credited',
                'transaction_type' => 'Withdrawal Repurchase',
                'debit_credit' => 2,
                'matching_date' => $today->toDateString(),
                'transaction_from_id' => $user->id,
                'transaction_to_id' => $user->id,
                'trigger_id' => $user->id,
                'created_user_id' => $user->id,
                'amount' => $amount * 0.20,
                'transaction_date' => $today,
                'status' => 1,
                'countStatus' => 0,
            ]);

            DB::commit();

            return $this->success([
                'summary' => EarningsSummaryService::calculate($user->id),
            ], 'Withdrawal processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API withdrawal failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->error('Failed to process withdrawal.', 500);
        }
    }
}
