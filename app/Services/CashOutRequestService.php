<?php

namespace App\Services;

use App\Models\BankEwalletSetup;
use App\Models\CashWalletTransaction;
use Carbon\Carbon;

class CashOutRequestService
{
    public static function dailyLimit(): float
    {
        return (float) config('cash_wallet.daily_cash_out_limit', 20000);
    }

    public static function rolling24hWithdrawalSum(int $userId): float
    {
        return (float) CashWalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 3)
            ->where('deleteStatus', 0)
            ->where('transaction_date', '>=', Carbon::now()->subHours(24))
            ->sum('amount');
    }

    public static function dailyRemaining(int $userId): float
    {
        return max(0, self::dailyLimit() - self::rolling24hWithdrawalSum($userId));
    }

    public static function maxWithdrawAmount(int $userId, float $balance): float
    {
        return min($balance, self::dailyRemaining($userId));
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public static function validateMethodSetup(?BankEwalletSetup $setup, string $method): array
    {
        if (! $setup) {
            return [
                'ok' => false,
                'message' => 'Please complete Bank & eWallet Setup before requesting cash out.',
            ];
        }

        return match ($method) {
            'bank_transfer' => self::hasBankSetup($setup)
                ? ['ok' => true]
                : [
                    'ok' => false,
                    'message' => 'Bank transfer details are incomplete. Please complete Bank & eWallet Setup.',
                ],
            'esewa' => filled($setup->esewa_wallet)
                ? ['ok' => true]
                : [
                    'ok' => false,
                    'message' => 'eSewa wallet number is not set. Please complete Bank & eWallet Setup.',
                ],
            'khalti' => filled($setup->khalti_wallet)
                ? ['ok' => true]
                : [
                    'ok' => false,
                    'message' => 'Khalti wallet number is not set. Please complete Bank & eWallet Setup.',
                ],
            default => ['ok' => false, 'message' => 'Invalid cash out method.'],
        };
    }

    public static function hasBankSetup(BankEwalletSetup $setup): bool
    {
        return filled($setup->bank_account_name)
            && filled($setup->account_holder_name)
            && filled($setup->account_number);
    }

    public static function hasEsewaSetup(?BankEwalletSetup $setup): bool
    {
        return $setup && filled($setup->esewa_wallet);
    }

    public static function hasKhaltiSetup(?BankEwalletSetup $setup): bool
    {
        return $setup && filled($setup->khalti_wallet);
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildPayoutSnapshot(BankEwalletSetup $setup, string $method): array
    {
        $base = ['cash_out_method' => $method];

        return match ($method) {
            'bank_transfer' => array_merge($base, [
                'cash_out_bank_name' => $setup->bank_account_name,
                'cash_out_account_type' => $setup->account_type,
                'cash_out_account_holder_name' => $setup->account_holder_name,
                'cash_out_account_number' => $setup->account_number,
            ]),
            'esewa' => array_merge($base, [
                'cash_out_esewa_wallet' => $setup->esewa_wallet,
            ]),
            'khalti' => array_merge($base, [
                'cash_out_khalti_wallet' => $setup->khalti_wallet,
            ]),
            default => $base,
        };
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public static function validateAmount(int $userId, float $amount, float $balance): array
    {
        if ($amount > $balance) {
            return [
                'ok' => false,
                'message' => 'Cash out amount cannot exceed your current cash wallet balance.',
            ];
        }

        $rollingSum = self::rolling24hWithdrawalSum($userId);
        $limit = self::dailyLimit();

        if ($rollingSum + $amount > $limit) {
            $remaining = max(0, $limit - $rollingSum);

            return [
                'ok' => false,
                'message' => sprintf(
                    'Daily cash out limit is NRS %s per 24 hours. You can withdraw up to NRS %s more in this period.',
                    number_format($limit, 2),
                    number_format($remaining, 2)
                ),
            ];
        }

        return ['ok' => true];
    }
}
