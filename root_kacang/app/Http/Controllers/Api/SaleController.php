<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use App\Services\SaleService;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SaleController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SaleRepository $sales,
        protected SaleService $service
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('role:operator', only: [
                'startToday',
                'addItem',
                'removeItem',
                'confirm',
            ]),
            new Middleware('role:manager', only: [
                'settle'
            ])
        ];
    }

    public function today(Request $request): JsonResponse
    {
        $sale = $this->sales->findToday($request->user());

        if (!$sale) {
            return response()->json(null, 404);
        }

        return response()->json($sale);
    }

    public function startToday(Request $request): JsonResponse
    {
        $sale = $this->sales->startToday($request->user());

        return response()->json($sale, 201);
    }

    public function confirm(Sale $sale): JsonResponse
    {
        return response()->json(
            $this->service->confirm($sale)
        );
    }

    public function settle(Sale $sale, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|float'
        ]);

        return response()->json(
            app(SettlementService::class)->settle($sale, $validated['amount'])
        );
    }

    public function cancel(Sale $sale): JsonResponse
    {
        return response()->json(
            $this->service->cancel($sale)
        );
    }

    public function addItem(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->sales->addItem(
                $sale->id,
                $validated['product_id'],
                $validated['quantity']
            )
        );
    }

    public function removeItem(Sale $sale, string $itemId): JsonResponse
    {
        return response()->json(
            $this->sales->removeItem($sale->id, $itemId)
        );
    }
}
