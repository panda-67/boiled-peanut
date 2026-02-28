<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use App\Services\Context\ActiveContextResolver;
use App\Services\SaleService;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function __construct(
        protected ActiveContextResolver $contextResolver,
        protected SaleRepository $sales,
        protected SaleService $service
    ) {}

    public function today(): JsonResponse
    {
        $user = Auth::user();
        $sale = $this->sales->findToday($user);

        if (!$sale) {
            return response()->json(null, 404);
        }

        return response()->json($sale);
    }

    public function startToday(): JsonResponse
    {
        $user = Auth::user();
        $context = $this->contextResolver->resolveForUser($user);

        $this->authorize('create', [Sale::class, $context]);

        $sale = $this->sales->startToday($user, $context);

        return response()->json($sale, 201);
    }

    public function addItem(Request $request, Sale $sale): JsonResponse
    {
        $context = $this->contextResolver->resolveForUser($request->user());
        $this->authorize('addItem', [$sale, $context]);

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
        $context = $this->contextResolver->resolveForUser(Auth::user());
        $this->authorize('removeItem', [$sale, $context]);

        return response()->json(
            $this->sales->removeItem($sale->id, $itemId)
        );
    }

    public function confirm(Sale $sale): JsonResponse
    {
        $context = $this->contextResolver->resolveForUser(Auth::user());
        $this->authorize('confirm', [$sale, $context]);

        return response()->json(
            $this->service->confirm($sale)
        );
    }

    public function cancel(Sale $sale): JsonResponse
    {
        $context = $this->contextResolver->resolveForUser(Auth::user());
        $this->authorize('cancel', [$sale, $context]);

        return response()->json(
            $this->service->cancel($sale)
        );
    }

    public function settle(Sale $sale, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $context = $this->contextResolver->resolveForUser($request->user());
        $this->authorize('settle', [$sale, $context]);

        return response()->json(
            app(SettlementService::class)->settle($sale, $validated['amount'])
        );
    }
}
