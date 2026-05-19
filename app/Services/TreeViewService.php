<?php

namespace App\Services;

use App\Models\BadgeFiveStars;
use App\Models\BadgeGigaStars;
use App\Models\BadgeMegaStars;
use App\Models\BadgeSevenStars;
use App\Models\BadgeThreeStars;
use App\Models\User;

class TreeViewService
{
    public function canViewUser(int $loggedInUserId, int $targetUserId): bool
    {
        if ($loggedInUserId === $targetUserId) {
            return true;
        }

        return $this->isInDownline($loggedInUserId, $targetUserId);
    }

    public function isInDownline(int $sourceUserId, int $targetUserId, int $maxDepth = 100): bool
    {
        $currentUserId = $targetUserId;
        $depth = 0;

        while ($currentUserId && $depth < $maxDepth) {
            $user = User::find($currentUserId);
            if (!$user || !$user->referred_by) {
                return false;
            }
            if ((int) $user->referred_by === $sourceUserId) {
                return true;
            }
            $currentUserId = (int) $user->referred_by;
            $depth++;
        }

        return false;
    }

    public function buildReferralTree(int $userId, int $currentLevel, int $maxLevels): ?array
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $treeNode = [
            'id' => $user->id,
            'name' => $user->name ?? 'Unknown',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'member_type' => $user->member_type ?? 'free',
            'active_status' => $user->activeStatus ?? 0,
            'referral_code' => $user->referral_code ?? '',
            'level' => $currentLevel,
            'highest_badge' => $this->getHighestBadge($user),
            'children' => [],
            'has_more_levels' => false,
        ];

        if ($currentLevel < $maxLevels) {
            $children = User::where('referred_by', $userId)->orderBy('id')->get();
            foreach ($children as $child) {
                $childNode = $this->buildReferralTree($child->id, $currentLevel + 1, $maxLevels);
                if ($childNode) {
                    $treeNode['children'][] = $childNode;
                }
            }
        } else {
            $treeNode['has_more_levels'] = User::where('referred_by', $userId)->exists();
        }

        return $treeNode;
    }

    public function getHighestBadge(User $user): ?array
    {
        if (BadgeGigaStars::where('user_id', $user->id)->exists()) {
            return ['name' => 'Mount Everest', 'initial' => 'E', 'rank' => 6];
        }
        if (BadgeMegaStars::where('user_id', $user->id)->exists()) {
            return ['name' => 'Kanchenjunga', 'initial' => 'K', 'rank' => 5];
        }
        if (BadgeSevenStars::where('user_id', $user->id)->exists()) {
            return ['name' => 'Makalu', 'initial' => 'M', 'rank' => 4];
        }
        if (BadgeFiveStars::where('user_id', $user->id)->exists()) {
            return ['name' => 'Dhaulagiri', 'initial' => 'D', 'rank' => 3];
        }
        if (BadgeThreeStars::where('user_id', $user->id)->exists()) {
            return ['name' => 'Manaslu', 'initial' => 'M', 'rank' => 2];
        }
        if ($user->member_type === 'paid' && $user->activeStatus == 1) {
            return ['name' => 'Annapurna', 'initial' => 'A', 'rank' => 1];
        }

        return null;
    }
}
