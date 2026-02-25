<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithLocation;
use Tests\Concerns\CalculateSale;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithLocation, CalculateSale;
}
