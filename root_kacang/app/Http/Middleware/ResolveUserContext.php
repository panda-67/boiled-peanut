<?php

namespace App\Http\Middleware;

use App\Models\BusinessDay;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user->activeLocation) {
            abort(403, 'No active location selected');
        }

        if (!BusinessDay::activeFor($user->activeLocation->location->id)) {
            abort(403, 'No active business day');
        }
        return $next($request);
    }
}
