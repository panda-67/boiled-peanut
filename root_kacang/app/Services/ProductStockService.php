<?php

namespace App\Services;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductTransaction;
use App\Models\Sale;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductStockService
{
    public function stockIn(
        Product $product,
        Location $location,
        float $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }

        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => ProductTransactionType::IN,
            'quantity'       => $qty,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function stockOut(
        Product $product,
        Location $location,
        float $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($product->stockAt($location) < $qty) {
            throw new \Exception("Stok produk tidak mencukupi di lokasi {$location->name}");
        }
        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => ProductTransactionType::OUT,
            'quantity'       => -abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function reserve(
        Product $product,
        Location $location,
        int $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = 'Product reserve'
    ): void {
        if ($qty <= 0) {
            throw new DomainException('INVALID_RESERVE_QUANTITY');
        }

        $exists = ProductTransaction::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('product_id', $product->id)
            ->where('type', ProductTransactionType::RESERVE)
            ->exists();

        if ($exists) {
            throw new DomainException('RESERVATION_ALREADY_EXISTS');
        }

        DB::transaction(function () use ($product, $location, $qty, $referenceType, $referenceId, $note) {

            // Lock stock rows for this product + location
            ProductTransaction::query()
                ->where('product_id', $product->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->get();

            $available = $product->availableAt($location);

            if ($available < $qty) {
                throw new DomainException('INSUFFICIENT_AVAILABLE_STOCK');
            }

            ProductTransaction::create([
                'product_id'     => $product->id,
                'location_id'    => $location->id,
                'type'           => ProductTransactionType::RESERVE,
                'quantity'       => $qty,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'note'           => $note,
                'date'           => now(),
            ]);
        });
    }

    public function finalizeSale(Sale $sale): void
    {
        $reserves = ProductTransaction::query()
            ->where('reference_type', ReferenceType::SALE)
            ->where('reference_id', $sale->id)
            ->where('type', ProductTransactionType::RESERVE)
            ->lockForUpdate()
            ->get();

        if ($reserves->isEmpty()) {
            throw new DomainException('NO_RESERVATION_FOUND');
        }

        $reserves->each(function ($reserve) use ($sale) {

            // prevent double finalize
            $alreadyOut = ProductTransaction::query()
                ->where('reference_type', ReferenceType::SALE)
                ->where('reference_id', $sale->id)
                ->where('product_id', $reserve->product_id)
                ->where('type', ProductTransactionType::OUT)
                ->exists();

            if ($alreadyOut) {
                throw new DomainException('SALE_ALREADY_FINALIZED');
            }

            // OUT
            ProductTransaction::create([
                'product_id'     => $reserve->product_id,
                'location_id'    => $reserve->location_id,
                'type'           => ProductTransactionType::OUT,
                'quantity'       => -$reserve->quantity,
                'reference_type' => ReferenceType::SALE,
                'reference_id'   => $sale->id,
                'note'           => 'Sale finalized',
                'date'           => now(),
            ]);

            // Reverse RESERVE
            ProductTransaction::create([
                'product_id'     => $reserve->product_id,
                'location_id'    => $reserve->location_id,
                'type'           => ProductTransactionType::RESERVE,
                'quantity'       => -$reserve->quantity,
                'reference_type' => ReferenceType::SALE,
                'reference_id'   => $sale->id,
                'note'           => 'Reserve released',
                'date'           => now(),
            ]);
        });
    }

    public function releaseSale(Sale $sale): void
    {
        $reserves = ProductTransaction::query()
            ->where('reference_type', ReferenceType::SALE)
            ->where('reference_id', $sale->id)
            ->where('type', ProductTransactionType::RESERVE)
            ->lockForUpdate()
            ->get();

        if ($reserves->isEmpty()) {
            return;
        }

        $reserves->each(function ($reserve) use ($sale) {
            ProductTransaction::create([
                'product_id'     => $reserve->product_id,
                'location_id'    => $reserve->location_id,
                'type'           => ProductTransactionType::RESERVE,
                'quantity'       => -$reserve->quantity,
                'reference_type' => ReferenceType::SALE,
                'reference_id'   => $sale->id,
                'note'           => 'Reserve released (cancel)',
                'date'           => now(),
            ]);
        });
    }
}
