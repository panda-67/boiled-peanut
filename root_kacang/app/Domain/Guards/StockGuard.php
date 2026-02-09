<?php

namespace App\Domain\Guards;

use App\Models\Location;
use App\Models\Product;
use DomainException;

final class StockGuard
{
    public static function ensureAvailableForSale(
        Product $product,
        Location $location,
        int $requiredQty
    ): void {
        $available = $product->availableAt($location);

        if ($available < $requiredQty) {
            throw new DomainException('INSUFFICIENT_AVAILABLE_STOCK');
        }
    }
}
