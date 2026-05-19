<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        return $this->success([
            'support_email' => env('SUPPORT_EMAIL', 'info@servicecafe.com'),
            'support_phone' => env('SUPPORT_PHONE', '+977 9766389515'),
            'support_address' => env('SUPPORT_ADDRESS', 'Lalitpur 14 khumaltar, Kathmandu, Nepal'),
        ]);
    }
}
