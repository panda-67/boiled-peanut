<?php

namespace App\Services;

use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\Location;
use App\Models\Sale;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductStockService
{
    public function stockIn(
        Item $product,
        Location $location,
        float $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }

        return StockMovement::create([
            'item_id'        => $product->id,
            'location_id'    => $location->id,
            'type'           => StockMovementType::IN,
            'quantity'       => $qty,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function stockOut(
        Item $product,
        Location $location,
        float $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): StockMovement {
        if ($product->stockAt($location) < $qty) {
            throw new \Exception("Stok produk tidak mencukupi di lokasi {$location->name}");
        }
        return StockMovement::create([
            'item_id'        => $product->id,
            'location_id'    => $location->id,
            'type'           => StockMovementType::OUT,
            'quantity'       => -abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function reserve(
        Item $product,
        Location $location,
        SaleStatus $saleStatus,
        int $qty,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = 'Product reserve'
    ): void {
        if ($qty <= 0) {
            throw new DomainException('INVALID_RESERVE_QUANTITY');
        }

        if ($saleStatus !== SaleStatus::DRAFT) {
            throw new DomainException('SALE_NOT_RESERVABLE');
        }

        $exists = StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('item_id', $product->id)
            ->where('type', StockMovementType::RESERVE)
            ->exists();

        if ($exists) {
            throw new DomainException('RESERVATION_ALREADY_EXISTS');
        }

        DB::transaction(function () use ($product, $location, $qty, $referenceType, $referenceId, $note) {

            // Lock stock rows for this product + location
            StockMovement::query()
                ->where('item_id', $product->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->get();

            $available = $product->availableAt($location);

            if ($available < $qty) {
                throw new DomainException('INSUFFICIENT_AVAILABLE_STOCK');
            }

            StockMovement::create([
                'item_id'        => $product->id,
                'location_id'    => $location->id,
                'type'           => StockMovementType::RESERVE,
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
        if ($sale->status !== SaleStatus::CONFIRMED) {
            throw new DomainException('SALE_NOT_FINALIZABLE');
        }

        DB::transaction(function () use ($sale) {

            $reserves = StockMovement::query()
                ->where('reference_type', ReferenceType::SALE)
                ->where('reference_id', $sale->id)
                ->where('type', StockMovementType::RESERVE)
                ->where('quantity', '>', 0) // only active reserves
                ->lockForUpdate()
                ->get();

            if ($reserves->isEmpty()) {
                throw new DomainException('NO_ACTIVE_RESERVATION_FOUND');
            }

            $reserves->each(function ($reserve) use ($sale) {

                // prevent double finalize
                $alreadyOut = StockMovement::query()
                    ->where('reference_type', ReferenceType::SALE)
                    ->where('reference_id', $sale->id)
                    ->where('item_id', $reserve->product_id)
                    ->where('type', StockMovementType::OUT)
                    ->exists();

                if ($alreadyOut) {
                    throw new DomainException('SALE_ALREADY_FINALIZED');
                }

                // OUT
                StockMovement::create([
                    'item_id'        => $reserve->product_id,
                    'location_id'    => $reserve->location_id,
                    'type'           => StockMovementType::OUT,
                    'quantity'       => -$reserve->quantity,
                    'reference_type' => ReferenceType::SALE,
                    'reference_id'   => $sale->id,
                    'note'           => 'Sale finalized',
                    'date'           => now(),
                ]);

                // Reverse RESERVE
                StockMovement::create([
                    'ite_id'         => $reserve->product_id,
                    'location_id'    => $reserve->location_id,
                    'type'           => StockMovementType::RESERVE,
                    'quantity'       => -$reserve->quantity,
                    'reference_type' => ReferenceType::SALE,
                    'reference_id'   => $sale->id,
                    'note'           => 'Reserve released',
                    'date'           => now(),
                ]);
            });
        });
    }

    public function releaseReservation(Sale $sale): void
    {
        if ($sale->status === SaleStatus::CANCELLED) {
            throw new DomainException('SALE_NOT_RELEASABLE');
        }

        DB::transaction(function () use ($sale) {
            $reserves = StockMovement::query()
                ->where('reference_type', ReferenceType::SALE)
                ->where('reference_id', $sale->id)
                ->where('type', StockMovementType::RESERVE)
                ->where('quantity', '>', 0) // active only
                ->lockForUpdate()
                ->get();

            if ($reserves->isEmpty()) {
                throw new DomainException('NO_ACTIVE_RESERVATION');
            }

            $reserves->each(function ($reserve) use ($sale) {
                StockMovement::create([
                    'item_id'        => $reserve->product_id,
                    'location_id'    => $reserve->location_id,
                    'type'           => StockMovementType::RESERVE,
                    'quantity'       => -$reserve->quantity,
                    'reference_type' => ReferenceType::SALE,
                    'reference_id'   => $sale->id,
                    'note'           => 'Reserve released (cancel)',
                    'date'           => now(),
                ]);
            });
        });
    }
}
