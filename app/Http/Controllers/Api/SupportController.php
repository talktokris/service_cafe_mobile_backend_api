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
            'support_email' => config('support.email'),
            'support_phone' => config('support.phone'),
            'support_address' => config('support.address'),
        ]);
    }
}
