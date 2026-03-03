<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Services\Context\ActiveContextResolver;
use App\Services\InventoryService;
use App\Services\ProductionService;
use App\Services\ProductTransferService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected ActiveContextResolver $contextResolver
    ) {}

    public function index(Request $request, InventoryService $service): JsonResponse
    {
        return response()->json($service->get(
            $request->query('type'),
            $request->query('location')
        ));
    }

    public function production(Product $product, Request $request, ProductionService $service): JsonResponse
    {
        // INFO: In case will have multiple central point and FE will send _id instead of id for location
        $central = Location::where('name', 'Central Kitchen')->firstOrFail();

        $this->authorize('open', [BusinessDay::class]);

        $context = $this->contextResolver->resolveCentralContext($request->user(), $central->_id);

        $this->authorize('create', [Production::class, $context]);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.material_id' => ['required', 'exists:materials,id'],
            'materials.*.quantity_used' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $service->execute($service->draft(
            $product,
            $validated['quantity'],
            collect($validated['materials']),
            $context,
        ));

        return response()->json(['message' => 'Production execute success.'], 201);
    }

    public function transfer(Product $product, Request $request, ProductTransferService $service): JsonResponse
    {
        $validated = $request->validate([
            'from'        => ['required', 'uuid'],
            'destination' => ['required', 'uuid'],
            'quantity'    => ['required', 'integer', 'min:1'],
        ]);

        $from = Location::firstWhere('_id', $validated['from']);
        $to = Location::firstWhere('_id', $validated['destination']);

        $service->transfer(
            $product,
            $from,
            $to,
            $validated['quantity']
        );

        return response()->json(['message' => 'Transfer success.'], 201);
    }

    public function stockIn(Material $material, Request $request, StockMovementService $service): JsonResponse
    {
        // INFO: In case will have multiple central point and FE will send _id instead of id for location
        $central = Location::where('name', 'Central Kitchen')->firstOrFail();

        $this->authorize('open', [BusinessDay::class]);

        $context = $this->contextResolver->resolveCentralContext($request->user(), $central->_id);

        $this->authorize('create', [Material::class, $context]);

        $validated = $request->validate([
            'quantity'    => ['required', 'numeric', 'min:0.0001'],
            'note'        => ['nullable', 'string'],
        ]);

        $service->inFromPurchase(
            material: $material,
            location: $context->location,
            qty: (float) $validated['quantity'],
            note: $validated['note'] ?? null
        );

        return response()->json([
            'message' => 'Material stock successfully added.'
        ]);
    }
}
