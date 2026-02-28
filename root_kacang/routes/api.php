<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/materials', [MaterialController::class, 'index']);

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

    Route::prefix('inventory')
        ->controller(InventoryController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/{product}/production', 'production');
            Route::post('/{product}/transfer', 'transfer');
        });
});

// AUTHENTICATION
Route::controller(AuthController::class)->group(function () {
    Route::get('/user', 'check');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});
