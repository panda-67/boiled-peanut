<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Settlement;
use App\Repositories\Eloquent\EloquentSaleRepository;
use DomainException;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        protected ProductStockService $stockService,
        protected EloquentSaleRepository $repository
    ) {}

    public function settle(Sale $sale, float $amountReceived): Settlement
    {
        return DB::transaction(function () use ($sale, $amountReceived) {

            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status !== SaleStatus::CONFIRMED) {
                throw new DomainException('SALE_NOT_CONFIRMABLE_FOR_SETTLEMENT');
            }

            if ($sale->settlement()->exists()) {
                throw new DomainException('SALE_ALREADY_SETTLED');
            }

            if (bccomp($amountReceived, $sale->total, 2) !== 0) {
                throw new DomainException('INVALID_SETTLEMENT_AMOUNT');
            }

            $settlement = Settlement::create([
                'sale_id'         => $sale->id,
                'amount_received' => $amountReceived,
                'received_at'     => now(),
                'method'          => 'warung',
            ]);

            $this->stockService->finalizeSale($sale);

            $this->repository->settle($sale->id);

            return $settlement;
        });
    }
}
