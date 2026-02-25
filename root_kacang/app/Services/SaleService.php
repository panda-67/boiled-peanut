<?php

namespace App\Services;

use App\Domain\Guards\LocationGuard;
use App\Enums\ReferenceType;
use App\Models\BusinessDay;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use App\Services\Context\ActiveContextResolver;
use DomainException;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        protected ActiveContextResolver $contextResolver,
        protected SaleRepository $repository,
        protected ProductStockService $stockService
    ) {}

    public function confirm(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {

            $context = $this->contextResolver->resolveForUser($sale->user);

            /** @var \App\Models\Sale $sale */
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->first();

            /** @var \App\Models\BusinessDay $businessDay */
            $businessDay = BusinessDay::whereKey($context->businessDay->id)
                ->lockForUpdate()
                ->first();

            if (! $user = $sale->user) {
                throw new DomainException('SALE_HAS_NO_OWNER');
            }

            LocationGuard::ensureSalePoint($user);

            if (! $sale->status->canBeConfirmed()) {
                throw new DomainException('CONFIRM_SALE_INVALID_STATE');
            }

            if ($sale->items->isEmpty()) {
                throw new DomainException('SALE_HAS_NO_ITEMS');
            }

            if ($sale->productTransactions()->exists()) {
                throw new DomainException('SALE_ALREADY_CONFIRMED');
            }

            if ($sale->location_id !== $context->location->id) {
                throw new DomainException('SALE_LOCATION_MISMATCH');
            }

            if ($sale->business_day_id !== $businessDay->id) {
                throw new DomainException('INVALID_BUSINESS_DAY');
            }

            if ($businessDay->isClosed()) {
                throw new DomainException('BUSINESS_DAY_ALREADY_CLOSED');
            }

            $sale->fill([
                'business_day_id' => $context->businessDay->id,
            ]);

            $this->repository->save($sale);

            $sale->items->each(function ($item) use ($context, $sale) {
                $product = $item->product;
                $qty     = $item->quantity;

                // Ledger write: RESERVE
                $this->stockService->reserve(
                    product: $product,
                    location: $context->location,
                    saleStatus: $sale->status,
                    qty: $qty,
                    referenceType: ReferenceType::SALE,
                    referenceId: $sale->id,
                );
            });

            $this->repository->confirm($sale->id);

            return $sale->fresh(['items.product']);
        });
    }

    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {

            $this->repository->cancel($sale->id);

            $this->stockService->releaseReservation($sale);

            return $sale->fresh();
        });
    }
}
