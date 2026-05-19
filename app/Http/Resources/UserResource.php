<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender,
            'country' => $this->country ?? null,
            'user_type' => $this->user_type,
            'member_type' => $this->member_type,
            'referral_code' => $this->referral_code,
            'referral_count' => $this->referral_count,
            'active_status' => $this->activeStatus,
            'wallet_balance' => $this->getCurrentWalletBalance(),
        ];
    }
}
