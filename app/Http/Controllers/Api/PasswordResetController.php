<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    use ApiResponse;

    private const CODE_EXPIRY_MINUTES = 15;

    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->where('deleteStatus', 0)->first();

        // Always return success to avoid email enumeration
        if (!$user || $user->user_type !== 'member') {
            return $this->success(null, 'If that email is registered, a reset code has been sent.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::CODE_EXPIRY_MINUTES);

        DB::table('password_reset_codes')->where('email', $email)->delete();
        DB::table('password_reset_codes')->insert([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code, self::CODE_EXPIRY_MINUTES));
        } catch (\Exception $e) {
            return $this->error('Unable to send email. Please try again later.', 500);
        }

        return $this->success([
            'expires_in_minutes' => self::CODE_EXPIRY_MINUTES,
        ], 'If that email is registered, a reset code has been sent.');
    }

    public function resetWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim($request->email));
        $code = $request->code;

        $record = DB::table('password_reset_codes')
            ->where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return $this->error('Invalid or expired reset code.', 422);
        }

        $user = User::where('email', $email)->where('deleteStatus', 0)->where('user_type', 'member')->first();
        if (!$user) {
            return $this->error('Account not found.', 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_codes')->where('email', $email)->delete();
        $user->tokens()->where('name', 'mobile-app')->delete();

        return $this->success(null, 'Password reset successfully. You can sign in with your new password.');
    }
}
