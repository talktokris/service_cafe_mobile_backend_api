<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $referralCount = $user->referrals()->where('deleteStatus', 0)->count();
        $orderCount = Order::where('memberUserId', $user->id)->where('deleteStatus', 0)->count();
        $monthOrderCount = Order::where('memberUserId', $user->id)
            ->where('deleteStatus', 0)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $this->success([
            'user' => new UserResource($user),
            'stats' => [
                'total_referrals' => $referralCount,
                'total_orders' => $orderCount,
                'month_orders' => $monthOrderCount,
                'wallet_balance' => $user->getCurrentWalletBalance(),
            ],
            'quick_links' => [
                'share_referral' => true,
                'orders' => true,
                'tree_view' => true,
                'earnings' => $user->member_type === 'paid',
                'cash_wallet' => $user->member_type === 'paid',
            ],
        ]);
    }
}
