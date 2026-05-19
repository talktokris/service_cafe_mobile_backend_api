<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $baseUrl = rtrim(config('app.referral_base_url', config('app.url')), '/');
        $hasCode = !empty($user->referral_code);
        $link = $hasCode ? "{$baseUrl}/join/{$user->referral_code}" : null;
        $shareMessage = $hasCode
            ? "Join Serve Cafe Forever Earning Program!\n\nEarn money while enjoying great food and drinks. Start your journey with us today!\n\nJoin here: {$link}"
            : null;

        return $this->success([
            'referral_code' => $user->referral_code,
            'has_referral_code' => $hasCode,
            'referral_link' => $link,
            'referral_count' => $user->referrals()->where('deleteStatus', 0)->count(),
            'share_message' => $shareMessage,
        ]);
    }
}
