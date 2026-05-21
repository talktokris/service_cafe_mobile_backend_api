<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'member' || (int) $user->deleteStatus !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Members only.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
