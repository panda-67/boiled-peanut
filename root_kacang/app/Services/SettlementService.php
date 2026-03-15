<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\Eloquent\EloquentSaleRepository;
use DomainException;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        protected ProductStockService $stockService,
        protected EloquentSaleRepository $repository,
        protected BusinessDayService $business
    ) {}

    public function settle(Sale $sale, float $amountReceived): Sale
    {
        return DB::transaction(function () use ($sale, $amountReceived) {

            /** @var \App\Models\Sale $sale */
            $sale = Sale::whereKey($sale->id)
                ->with(['user:id', 'location:id', 'businessDay'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->businessDay->isClosed()) {
                throw new DomainException('Business day is closed.');
            }

            if (! $sale->status->canBeSettled()) {
                throw new DomainException('Sale is not confirmed.');
            }

            if ($sale->settlement()->exists()) {
                throw new DomainException('Sale already settled.');
            }

            if (bccomp($amountReceived, $sale->total, 2) !== 0) {
                throw new DomainException('Invalid settlement amount.');
            }

            $sale->settlement()->create([
                'amount_received' => $amountReceived,
                'received_at'     => now(),
                'method'          => 'cash',
            ]);

            $this->stockService->finalizeSale($sale);

            $this->repository->settle($sale->id);

            return $sale->fresh(['settlement']);
        });
    }
}
