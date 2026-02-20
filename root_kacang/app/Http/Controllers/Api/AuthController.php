<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth:sanctum', only: ['check', 'logout'])];
    }

    /**
     * Log User In.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'user' => $user,
            'message' => 'Authenticated'
        ])->cookie(
            'access_token',
            $token,
            60 * 24,   // 1 hari
            '/',
            null,
            false,
            true,      // httpOnly
            false,
            'Lax'
        );
    }

    /**
     * User check
     */
    public function check(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load([
            'role',
            'locations',
            'managerActiveLocation.location',
            'activeLocation.location',
        ]);

        $locationId = $request->header('X-Active-Location');

        $activeLocation = null;

        if ($user->whomActAs(UserRole::MANAGER)) {

            // 1. If header provided → update context
            if ($locationId) {

                $location = $user->locations()
                    ->where('locations.id', $locationId)
                    ->first();

                if (!$location) {
                    abort(403, 'LOCATION_NOT_ALLOWED');
                }

                $user->setManagerActiveLocation($location);
                $activeLocation = $location;
            } else {

                // 2. Use existing stored context
                $activeLocation = $user->managerActiveLocation?->location;

                // 3. If none exists → set default
                if (!$activeLocation) {

                    $default = $user->locations->first();

                    if (!$default) {
                        abort(403, 'MANAGER_HAS_NO_ASSIGNED_LOCATION');
                    }

                    $user->setManagerActiveLocation($default);
                    $activeLocation = $default;
                }
            }
        } else {
            // Operator logic
            $activeLocation = $user->activeLocation?->location;

            if (!$activeLocation) {
                abort(403, 'USER_HAS_NO_ACTIVE_LOCATION');
            }
        }

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role?->code,
            'roleName' => $user->role?->name,
            'activeLocation' => $activeLocation?->id,
            'activeLocationName' => $activeLocation?->name,
            'accessibleLocations' => $user->locations->map(fn($location) => [
                'id' => $location->id,
                'name' => $location->name
            ]),
        ]);
    }

    /**
     * Log User Out.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()
            ->json(['message' => 'Logged out'])
            ->cookie('access_token', '', -1);
    }
}
