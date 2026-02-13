<?php

namespace App\Services\Context;

use App\Models\BusinessDay;
use App\Models\Location;

class ActiveContext
{
    public function __construct(
        public readonly Location $location,
        public readonly BusinessDay $businessDay
    ) {}
}
