<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
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
            'message' => 'Authenticated successfully.'
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

        $activeLocation = $this->resolveActiveLocation($user, $locationId);

        $payload = $this->buildPayload($user, $activeLocation);

        if ($user->whomActAs(UserRole::MANAGER)) {
            $payload['accessibleLocations'] = $user->locations
                ->map(fn($loc) => [
                    'id'   => $loc->_id,
                    'name' => $loc->name,
                ]);
        }

        if ($user->whomActAs(UserRole::OWNER)) {
            $payload['accessibleLocations'] = $this->getActiveLocation()
                ->map(fn($loc) => [
                    'id'    => $loc->_id,
                    'name'  => $loc->name
                ]);
        }

        $businessDay = $this->resolveBusinessDay($activeLocation);

        if ($businessDay) {
            $payload['businessDay'] = [
                'id'   => $businessDay->id,
                'open' => $businessDay->isOpen(),
            ];
        }

        return response()->json($payload);
    }

    /**
     * Log User Out.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()
            ->json(['message' => 'Logged out successfully.'])
            ->cookie('access_token', '', -1);
    }

    private function resolveActiveLocation(User $user, ?string $locationId): ?Location
    {
        if ($user->whomActAs(UserRole::MANAGER)) {
            return $this->resolveManagerLocation($user, $locationId);
        }

        if ($user->whomActAs(UserRole::OWNER)) {
            return $this->resolveOwnerLocation($user, $locationId);
        }

        return $this->resolveOperatorLocation($user);
    }

    private function resolveOwnerLocation(User $user, ?string $locationId): Location
    {
        if ($locationId) {
            $location = Location::where('is_active', true)
                ->where('_id', $locationId)
                ->firstOrFail();

            $user->setOwnerActiveLocation($location);
            return $location;
        }

        $existing = $user->ownerActiveLocation?->location;
        if ($existing) {
            return $existing;
        }

        $default = Location::where('is_active', true)->first();

        $user->setOwnerActiveLocation($default);

        return $default;
    }

    private function resolveManagerLocation(User $user, ?string $locationId): Location
    {
        if ($locationId) {
            $location = $user->locations()
                ->where('locations._id', $locationId)
                ->first();

            if (!$location) {
                abort(403, 'LOCATION_NOT_ALLOWED');
            }

            $user->setManagerActiveLocation($location);
            return $location;
        }

        $existing = $user->managerActiveLocation?->location;
        if ($existing) {
            return $existing;
        }

        $default = $user->locations->first();

        if (!$default) {
            abort(403, 'MANAGER_HAS_NO_ASSIGNED_LOCATION');
        }

        $user->setManagerActiveLocation($default);

        return $default;
    }

    private function resolveOperatorLocation(User $user): Location
    {
        $location = $user->activeLocation?->location;

        if (!$location) {
            abort(403, 'USER_HAS_NO_ACTIVE_LOCATION');
        }

        return $location;
    }

    private function resolveBusinessDay(?Location $location): ?BusinessDay
    {
        if (!$location) {
            return null;
        }

        return BusinessDay::query()
            ->where('location_id', $location->id)
            ->latest('date')
            ->first();
    }

    private function buildPayload(User $user, Location $activeLocation): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role?->code,
            'roleName' => $user->role?->name,
            'activeLocation' => $activeLocation->_id,
            'activeLocationName' => $activeLocation->name,
        ];
    }

    private function getActiveLocation(): Collection
    {
        return Location::where('is_active', true)->get();
    }
}
