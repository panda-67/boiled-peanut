<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
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
            'sale_date'      => now(),
            'subtotal'       => 0,
            'total'          => 0,
            'status'         => SaleStatus::DRAFT,
            'location_id'    => Location::factory(),
            'user_id'        => User::factory(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn() => [
            'user_id' => $user->id,
        ]);
    }

    public function atLocation(Location $location): self
    {
        return $this->state(fn() => [
            'location_id' => $location->id,
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn() => [
            'status' => SaleStatus::CONFIRMED,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn() => [
            'status' => SaleStatus::CANCELLED,
        ]);
    }
}
