<?php

namespace App\Repositories;

use App\Domain\Sales\Data\CreateSaleData;
use App\Models\Sale;
use App\Models\User;

interface SaleRepository
{
    public function findToday(User $user): ?Sale;

    public function startToday(User $user): Sale;

    public function addItem(string $saleId, string $productId, int $qty): Sale;

    public function removeItem(string $saleId, string $itemId): Sale;

    public function createDraft(CreateSaleData $data): Sale;

    public function confirm(string $id): void;

    public function settle(string $id): void;

    public function cancel(string $id): void;

    public function save(Sale $sale): void;
}
