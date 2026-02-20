<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);

    Route::prefix('sales')
        ->controller(SaleController::class)
        ->group(function () {

            Route::get('/today', 'today');
            Route::post('/start', 'startToday');

            Route::post('/{sale}/items', 'addItem');
            Route::delete('/{sale}/items/{item}', 'removeItem');

            Route::post('/{sale}/confirm', 'confirm');
            Route::post('/{sale}/settle', 'settle');
            Route::post('/{sale}/cancel', 'cancel');
        });
});

// AUTHENTICATION
Route::controller(AuthController::class)->group(function () {
    Route::get('/user', 'check');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});
