<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankEwalletSetup;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use ApiResponse;

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'country' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'regex:/^\+977[0-9]{9,10}$/', 'min:13', 'max:14'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $firstName = array_key_exists('first_name', $validated)
            ? (filled($validated['first_name'] ?? null)
                ? strip_tags(trim($validated['first_name']))
                : null)
            : $user->first_name;
        $lastName = array_key_exists('last_name', $validated)
            ? (filled($validated['last_name'] ?? null)
                ? strip_tags(trim($validated['last_name']))
                : null)
            : $user->last_name;

        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => User::buildDisplayName($firstName, $lastName, $user->name),
            'gender' => $validated['gender'] ?? $user->gender,
            'country' => $validated['country'] ?? $user->country,
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
        ]);

        return $this->success(new UserResource($user->fresh()), 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(null, 'Password updated successfully.');
    }

    public function updateReferral(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'referral_code' => [
                'required',
                'string',
                'min:3',
                'max:60',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update([
            'referral_code' => trim($request->referral_code),
        ]);

        return $this->success(new UserResource($user->fresh()), 'Referral code updated successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'string', 'in:Delete'],
        ]);

        $user = $request->user();

        $user->update([
            'name' => '',
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'address' => null,
            'gender' => null,
            'country' => null,
            'email' => 'deleted_'.$user->id.'_'.time().'@deleted.servecafe',
            'password' => Hash::make(Str::random(64)),
            'deleteStatus' => 1,
            'activeStatus' => 0,
        ]);

        $user->tokens()->delete();

        return $this->success(null, 'Your account has been deleted successfully.');
    }

    public function bankEwalletSetup(Request $request)
    {
        $setup = $request->user()->bankEwalletSetup;

        return $this->success([
            'bank_account_name' => $setup?->bank_account_name,
            'account_type' => $setup?->account_type,
            'account_holder_name' => $setup?->account_holder_name,
            'account_number' => $setup?->account_number,
            'esewa_wallet' => $setup?->esewa_wallet,
            'khalti_wallet' => $setup?->khalti_wallet,
        ], 'Bank and e-wallet setup fetched successfully.');
    }

    public function upsertBankEwalletSetup(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'in:saving,current'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'esewa_wallet' => ['nullable', 'string', 'max:20'],
            'khalti_wallet' => ['nullable', 'string', 'max:20'],
        ]);

        $normalize = static fn (?string $value): ?string => filled($value) ? trim($value) : null;

        $setup = BankEwalletSetup::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bank_account_name' => $normalize($validated['bank_account_name'] ?? null),
                'account_type' => $normalize($validated['account_type'] ?? null),
                'account_holder_name' => $normalize($validated['account_holder_name'] ?? null),
                'account_number' => $normalize($validated['account_number'] ?? null),
                'esewa_wallet' => $normalize($validated['esewa_wallet'] ?? null),
                'khalti_wallet' => $normalize($validated['khalti_wallet'] ?? null),
            ]
        );

        return $this->success([
            'bank_account_name' => $setup->bank_account_name,
            'account_type' => $setup->account_type,
            'account_holder_name' => $setup->account_holder_name,
            'account_number' => $setup->account_number,
            'esewa_wallet' => $setup->esewa_wallet,
            'khalti_wallet' => $setup->khalti_wallet,
        ], 'Bank and e-wallet setup saved successfully.');
    }
}
