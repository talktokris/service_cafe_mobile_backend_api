<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TreeViewService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TreeViewController extends Controller
{
    use ApiResponse;

    public function __construct(private TreeViewService $treeView) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $startUserId = (int) $request->input('start_user_id', $user->id);

        if (!$this->treeView->canViewUser($user->id, $startUserId)) {
            $startUserId = $user->id;
        }

        $treeData = $this->treeView->buildReferralTree($startUserId, 1, 5);
        $startUser = User::find($startUserId);
        $parentInfo = null;

        if ($startUser && $startUser->referred_by && $startUser->referred_by != $user->id) {
            $parentUser = User::find($startUser->referred_by);
            if ($parentUser && $this->treeView->canViewUser($user->id, $parentUser->id)) {
                $parentInfo = [
                    'id' => $parentUser->id,
                    'name' => $parentUser->name,
                ];
            }
        }

        return $this->success([
            'tree' => $treeData,
            'current_root_user_id' => $startUserId,
            'parent_info' => $parentInfo,
            'logged_in_user_id' => $user->id,
        ]);
    }

    public function children(Request $request)
    {
        $request->validate(['user_id' => 'required|integer']);

        $user = $request->user();
        $userId = (int) $request->user_id;

        if (!$this->treeView->canViewUser($user->id, $userId)) {
            return $this->error('Access denied.', 403);
        }

        $currentLevel = (int) $request->input('current_level', 1);
        $treeData = $this->treeView->buildReferralTree($userId, $currentLevel, $currentLevel + 4);

        return $this->success($treeData);
    }
}
