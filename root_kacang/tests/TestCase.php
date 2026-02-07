<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithLocation;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithLocation;
}
