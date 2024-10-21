<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Member;

class IdentifyUserOrMember
{
    public function handle(Request $request, Closure $next)
    {
        // First, authenticate with sanctum middleware
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Determine whether the authenticated user is a User or Member
        $token = $request->user()->currentAccessToken();

        // Check if the token belongs to a User or Member
        if ($token->tokenable_type === User::class) {
            // Authenticated as a User
            $request->attributes->set('auth_type', 'user');
            $request->attributes->set('authenticated_user', $request->user());
        } elseif ($token->tokenable_type === Member::class) {
            // Authenticated as a Member
            $request->attributes->set('auth_type', 'member');
            $request->attributes->set('authenticated_member', $request->user());
        } else {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
