<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessDayController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/materials', [MaterialController::class, 'index']);

    Route::prefix('business-day')
        ->controller(BusinessDayController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/open', 'open');
            Route::post('/close', 'close');
        });

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
            Route::post('/{material}/stock-in', 'stockIn');
        });

    Route::prefix('reports')
        ->controller(ReportController::class)
        ->group(function () {
            Route::get('/summary', 'summary');
            // sales
            Route::get('/sales-detail', 'salesDetail');
            Route::get('/cash-difference', 'cashDifference');
            Route::get('/production-vs-sales', 'productionVsSales');
            Route::get('/out-standing-sales', 'outstandingSales');
            // stocks
            Route::get('/product-stock', 'productStock');
            Route::get('/material-stock', 'materialStock');
            Route::get('/{material}/material-ledger', 'materialLedger');
            Route::get('/mateial-daily-usage', 'dailyMaterialUsage');
        });
});

// AUTHENTICATION
Route::controller(AuthController::class)->group(function () {
    Route::get('/user', 'check');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});
