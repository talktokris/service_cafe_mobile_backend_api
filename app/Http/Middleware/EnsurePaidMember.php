<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaidMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'member' || $user->member_type !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This feature is available for paid members only.',
                'data' => ['requires_paid' => true],
            ], 403);
        }

        return $next($request);
    }
}
