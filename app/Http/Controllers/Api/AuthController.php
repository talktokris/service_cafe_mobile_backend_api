<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('deleteStatus', 0)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->user_type !== 'member') {
            return $this->error('This app is for members only.', 403);
        }

        if ($user->activeStatus != 1) {
            return $this->error('Your account is inactive. Please contact support.', 403);
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            Log::error('Mobile login blocked: personal_access_tokens table missing');

            return $this->error(
                'Mobile login is not configured on the server. Run: php artisan migrate --force',
                503
            );
        }

        try {
            $user->tokens()->where('name', 'mobile-app')->delete();
            $token = $user->createToken('mobile-app')->plainTextToken;
        } catch (QueryException $e) {
            Log::error('Mobile login token error', ['email' => $request->email, 'error' => $e->getMessage()]);

            return $this->error('Unable to sign in right now. Please contact support.', 503);
        } catch (\Throwable $e) {
            Log::error('Mobile login failed', ['email' => $request->email, 'error' => $e->getMessage()]);

            return $this->error('Unable to complete sign in. Please try again later.', 500);
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()));
    }
}
