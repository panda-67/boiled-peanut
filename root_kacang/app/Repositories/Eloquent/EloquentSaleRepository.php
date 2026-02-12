<?php

namespace App\Repositories\Eloquent;

use App\Domain\Sales\Data\CreateSaleData;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepository
{
    public function findOrFail(string $id): Sale
    {
        return Sale::query()->lockForUpdate()->findOrFail($id);
    }

    public function createDraft(CreateSaleData $data): Sale
    {
        return Sale::create([
            ...$data->toPersistenceArray(),
            'status' => SaleStatus::DRAFT,
        ]);
    }

    public function save(Sale $sale): void
    {
        $sale->save();
    }

    public function confirm(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->confirm();
            $sale->save();
        });
    }

    public function settle(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->settle();
            $sale->save();
        });
    }

    public function cancel(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->cancel();
            $sale->save();
        });
    }
}
