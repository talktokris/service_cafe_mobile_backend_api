<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderOtpController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('memberUserId', $user->id)
            ->where('deleteStatus', 0)
            ->with([
                'table:id,tableShortName,tableShortFullName',
                'branch:id,name,companyName',
                'headOffice:id,companyName',
            ])
            ->withCount(['orderItems as item_count' => function ($q) {
                $q->where('deleteStatus', 0);
            }])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'orderShortName' => $order->orderShortName ?? 'Order #'.$order->id,
                'created_at' => $order->created_at,
                'sellingPrice' => $order->sellingPrice,
                'paymentStatus' => $order->paymentStatus,
                'paymentType' => $order->paymentType,
                'txn_otp' => $order->txn_otp,
                'otp_code' => $order->otp_code,
                'otp_status' => $order->otp_status,
                'item_count' => $order->item_count,
                'table' => $order->table,
                'branch' => $order->branch,
                'head_office' => $order->headOffice,
                'headOffice' => $order->headOffice,
            ]);

        return $this->success($orders);
    }
}
