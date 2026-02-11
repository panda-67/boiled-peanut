<?php

namespace App\Services\Context;

use App\Models\BusinessDay;

class ActiveContext
{
    public function __construct(
        public readonly int $locationId,
        public readonly BusinessDay $businessDay
    ) {}
}
