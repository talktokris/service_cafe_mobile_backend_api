<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $user->tokens()->where('name', 'mobile-app')->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

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
