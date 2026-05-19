<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    use ApiResponse;

    public function validateReferral(string $referralCode)
    {
        $referrer = User::where('referral_code', $referralCode)->where('deleteStatus', 0)->first();

        if (!$referrer) {
            return $this->error('Invalid referral code. Please check and try again.', 404);
        }

        return $this->success([
            'referral_code' => $referralCode,
            'referrer_name' => trim(($referrer->first_name ?? '').' '.($referrer->last_name ?? '')) ?: $referrer->name,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $referrer = User::where('referral_code', $request->referral_code)->where('deleteStatus', 0)->first();
        if (!$referrer) {
            return $this->error('Invalid referral code.', 422);
        }

        $user = User::create([
            'name' => trim($request->first_name.' '.$request->last_name),
            'first_name' => trim($request->first_name),
            'last_name' => trim($request->last_name),
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'user_type' => 'member',
            'member_type' => 'free',
            'referral_code' => null,
            'referred_by' => $referrer->id,
            'activeStatus' => 1,
            'deleteStatus' => 0,
        ]);

        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            Log::error('Welcome email failed: '.$e->getMessage());
        }

        return $this->success([
            'email' => $user->email,
        ], 'Registration successful. You can now sign in.');
    }
}
