<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BadgeFiveStars;
use App\Models\BadgeGigaStars;
use App\Models\BadgeMegaStars;
use App\Models\BadgeSevenStars;
use App\Models\BadgeThreeStars;
use App\Models\MemberUplineRank;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BadgesController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $memberRank = MemberUplineRank::with([
            'referralUser:id,first_name,last_name,name',
            'threeStarUser:id,first_name,last_name,name',
            'fiveStarUser:id,first_name,last_name,name',
            'sevenStarUser:id,first_name,last_name,name',
            'megaStarUser:id,first_name,last_name,name',
            'gigaStarUser:id,first_name,last_name,name',
        ])->where('user_id', $user->id)->first();

        $badges = [
            'annapurna' => $user->member_type === 'paid' && $user->activeStatus == 1,
            'manaslu' => BadgeThreeStars::where('user_id', $user->id)->exists(),
            'dhaulagiri' => BadgeFiveStars::where('user_id', $user->id)->exists(),
            'cho_oyu' => BadgeSevenStars::where('user_id', $user->id)->exists(),
            'makalu' => BadgeMegaStars::where('user_id', $user->id)->exists(),
            'kanchenjunga' => BadgeGigaStars::where('user_id', $user->id)->exists(),
        ];

        $levels = [
            ['key' => 'annapurna', 'name' => 'Annapurna', 'unlocked' => $badges['annapurna']],
            ['key' => 'manaslu', 'name' => 'Manaslu', 'unlocked' => $badges['manaslu']],
            ['key' => 'dhaulagiri', 'name' => 'Dhaulagiri', 'unlocked' => $badges['dhaulagiri']],
            ['key' => 'cho_oyu', 'name' => 'Makalu', 'unlocked' => $badges['cho_oyu']],
            ['key' => 'makalu', 'name' => 'Kanchenjunga', 'unlocked' => $badges['makalu']],
            ['key' => 'kanchenjunga', 'name' => 'Mount Everest', 'unlocked' => $badges['kanchenjunga']],
        ];

        return $this->success([
            'badges' => $badges,
            'levels' => $levels,
            'member_rank' => $memberRank,
        ]);
    }
}
