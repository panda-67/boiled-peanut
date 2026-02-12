<?php

namespace App\Repositories;

use App\Domain\Sales\Data\CreateSaleData;
use App\Models\Sale;

interface SaleRepository
{
    public function createDraft(CreateSaleData $data): Sale;

    public function findOrFail(string $id): Sale;

    public function confirm(string $id): void;

    public function settle(string $id): void;

    public function save(Sale $sale): void;
}
