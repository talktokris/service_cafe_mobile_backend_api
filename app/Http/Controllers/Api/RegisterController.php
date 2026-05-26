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

    private function findReferrerByCode(string $referralCode): ?User
    {
        $code = trim($referralCode);

        if ($code === '' || ! preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $code)) {
            return null;
        }

        return User::where('deleteStatus', 0)
            ->whereRaw('LOWER(referral_code) = LOWER(?)', [$code])
            ->first();
    }

    public function validateReferral(string $referralCode)
    {
        $code = trim(urldecode($referralCode));

        if ($code === '' || ! preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $code)) {
            return $this->error('Invalid referral code format.', 422);
        }

        $referrer = $this->findReferrerByCode($code);

        if (! $referrer) {
            return $this->error('Invalid referral code. Please check and try again.', 404);
        }

        return $this->success([
            'referral_code' => $referrer->referral_code,
            'referrer_name' => trim(($referrer->first_name ?? '').' '.($referrer->last_name ?? '')) ?: $referrer->name,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'referral_code' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|accepted',
        ]);

        $referrer = $this->findReferrerByCode($request->referral_code);
        if (! $referrer) {
            return $this->error('Invalid referral code.', 422);
        }

        $user = User::create([
            'name' => User::buildDisplayName($request->first_name, $request->last_name),
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
            'terms_accepted_at' => now(),
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
