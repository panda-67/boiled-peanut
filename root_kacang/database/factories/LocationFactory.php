<?php

namespace Database\Factories;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->company,
            'type'      => LocationType::SALE_POINT,
            'is_active' => 1,
        ];
    }

    public function central(): self
    {
        return $this->state(fn() => [
            'name' => 'Central Kitchen',
            'type' => LocationType::CENTRAL,
        ]);
    }

    public function salesPoint(): self
    {
        return $this->state(fn() => [
            'type' => LocationType::SALE_POINT,
        ]);
    }
}
