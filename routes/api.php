<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgesController;
use App\Http\Controllers\Api\CashWalletController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EarningsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\TreeViewController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/referral/{referralCode}', [RegisterController::class, 'validateReferral']);
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetWithCode']);
    Route::middleware(['auth:sanctum', 'member'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum', 'member'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/referral', [ReferralController::class, 'index']);
    Route::get('/support', [SupportController::class, 'index']);
    Route::get('/tree-view', [TreeViewController::class, 'index']);
    Route::post('/tree-view/children', [TreeViewController::class, 'children']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::put('/profile/referral', [ProfileController::class, 'updateReferral']);

    Route::middleware('paid.member')->group(function () {
        Route::get('/earnings', [EarningsController::class, 'index']);
        Route::post('/earnings/withdraw', [EarningsController::class, 'withdraw']);
        Route::get('/cash-wallet', [CashWalletController::class, 'index']);
        Route::post('/cash-wallet/cash-out', [CashWalletController::class, 'cashOut']);
        Route::post('/cash-wallet/transfer', [CashWalletController::class, 'transfer']);
        Route::get('/badges', [BadgesController::class, 'index']);
    });
});
