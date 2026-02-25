<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $allowed = array_map(
            fn($r) => UserRole::from($r),
            $roles
        );

        if (! $user->role || ! in_array($user->role->code, $allowed, true)) {
            abort(403, 'Forbidden. Insufficient role.');
        }

        return $next($request);
    }
}
