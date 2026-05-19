<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::where('memberUserId', $user->id)
            ->where('deleteStatus', 0)
            ->with(['headOffice:id,companyName', 'branch:id,name', 'creator:id,name,first_name,last_name'])
            ->orderByDesc('created_at');

        if ($request->filled('order_id')) {
            $query->where('id', 'like', '%'.$request->order_id.'%');
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->paginate($request->integer('per_page', 20));

        return $this->success($orders);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('memberUserId', $user->id)
            ->where('deleteStatus', 0)
            ->with(['orderItems.menuItem', 'headOffice', 'branch'])
            ->first();

        if (!$order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success($order);
    }
}
