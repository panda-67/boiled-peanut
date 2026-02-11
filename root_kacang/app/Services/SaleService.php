<?php

namespace App\Services;

use App\Domain\Guards\LocationGuard;
use App\Domain\Guards\StockGuard;
use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Models\Sale;
use App\Services\Context\ActiveContextResolver;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        protected ActiveContextResolver $contextResolver
    ) {}

    public function confirm(Sale $sale): Sale
    {
        if (!$user = $sale->user) {
            throw new \DomainException('SALE_HAS_NO_OWNER');
        }

        LocationGuard::ensureSalePoint($user);

        if (!$sale->status->canBeConfirmed()) {
            throw new \DomainException('CONFIRM_SALE_INVALID_STATE');
        }

        if ($sale->items->isEmpty()) {
            throw new \DomainException('SALE_HAS_NO_ITEMS');
        }

        if ($sale->productTransactions()->exists()) {
            throw new \DomainException('SALE_ALREADY_CONFIRMED');
        }

        return DB::transaction(function () use ($sale) {

            $context = $this->contextResolver->resolveForUser($sale->user);

            $subtotal = 0;

            foreach ($sale->items as $item) {
                $product = $item->product;
                $qty     = $item->quantity;

                // 1. Lock line total (masih DRAFT → boleh)
                $lineTotal = $qty * $item->unit_price;

                $item->update([
                    'total_price' => $lineTotal,
                ]);

                // 2. Guard stok
                StockGuard::ensureAvailableForSale(
                    $product,
                    $sale->location,
                    $qty
                );

                // 3. Ledger write: RESERVE
                app(ProductStockService::class)->reserve(
                    $product,
                    $sale->location,
                    $qty,
                    ProductTransactionType::RESERVE,
                    ReferenceType::SALE,
                    $sale->id,
                    'Product reserve'
                );

                $subtotal += $lineTotal;
            }

            // 4. Lock financial totals (MASIH DRAFT)
            $sale->fill([
                'subtotal'        => $subtotal,
                'total'           => $subtotal - $sale->discount + $sale->tax,
                'bussines_day_id' => $context->businessDay->id,
            ]);

            $sale->save(); // aman, status masih DRAFT

            // 5. State transition (SATU-SATUNYA TEMPAT)
            $sale->confirm();
            $sale->save();

            return $sale;
        });
    }
}
