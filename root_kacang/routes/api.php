<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Auth;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('sales', SaleController::class);
});

// AUTHENTICATION
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = $request->user();

    $token = $user->createToken('web')->plainTextToken;

    return response()
        ->json([
            'user' => $user,
            'message' => 'Authenticated'
        ])
        ->cookie(
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
});

Route::post('/logout', function (Request $request) {

    $request->user()->currentAccessToken()->delete();

    return response()
        ->json(['message' => 'Logged out'])
        ->cookie('access_token', '', -1);
})->middleware('auth:sanctum')->name('logout');
