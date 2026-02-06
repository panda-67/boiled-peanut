<?php

namespace App\Domain\Inventory;

final class ReferenceType
{
    public const SALE       = 'sale';
    public const PRODUCTION = 'production';
    public const PURCHASE   = 'purchase';
    public const ADJUSTMENT = 'adjustment';
    public const INITIAL    = 'initial';

    private function __construct()
    {
        // prevent instantiation
    }
}
