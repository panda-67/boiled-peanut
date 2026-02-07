<?php

namespace App\Domain\Inventory;

final class ReferenceType
{
    public const PURCHASE   = 'purchase';
    public const PRODUCTION = 'production';
    public const TRANSFER   = 'transfer';
    public const SALE       = 'sale';
    public const ADJUSTMENT = 'adjustment';
    public const INITIAL    = 'initial';

    private function __construct()
    {
        // prevent instantiation
    }
}
