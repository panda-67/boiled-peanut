<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Context\ActiveContextResolver;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ActiveContextResolver $contextResolver
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $context = $this->contextResolver->resolveForUser($request->user());
        $locationId = $context->location->id;

        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.selling_price',
            ])
            ->selectSub(function ($q) use ($locationId) {
                $q->from('product_transactions')
                    ->whereColumn('product_id', 'products.id')
                    ->where('location_id', $locationId)
                    ->whereIn('type', [
                        ProductTransactionType::IN,
                        ProductTransactionType::OUT,
                    ])
                    ->selectRaw('COALESCE(SUM(quantity), 0)');
            }, 'stock')
            ->selectSub(function ($q) use ($locationId) {
                $q->from('product_transactions')
                    ->whereColumn('product_id', 'products.id')
                    ->where('location_id', $locationId)
                    ->where('type', ProductTransactionType::RESERVE)
                    ->selectRaw('COALESCE(SUM(quantity), 0)');
            }, 'reserved')
            ->latest()
            ->get()
            ->map(function ($product) {
                $stock = (float) $product->stock;
                $reserved = (float) $product->reserved;

                return [
                    'id'        => $product->id,
                    'name'      => $product->name,
                    'price'     => $product->selling_price,
                    'stock'     => $stock,
                    'available' => $stock - $reserved,
                ];
            });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
