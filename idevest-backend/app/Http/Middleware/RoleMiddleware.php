<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has one of the given roles.
     *
     * Usage in routes: ->middleware(['auth:sanctum', 'role:admin'])
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account blocked.',
            ], 403);
        }

        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden: requires role ' . implode('|', $roles) . '.',
            ], 403);
        }

        return $next($request);
    }
}
