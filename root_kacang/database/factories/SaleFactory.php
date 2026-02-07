<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'sale_date'   => $this->faker->date(),
            'total'  => 0,
            'status' => 'draft',
            'location_id' => Location::factory(),
            'user_id'     => User::factory(),
        ];
    }

    public function confirmed(): self
    {
        return $this->state(fn() => [
            'status' => 'confirmed',
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn() => [
            'status' => 'cancelled',
        ]);
    }
}
