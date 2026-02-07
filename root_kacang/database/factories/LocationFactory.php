<?php

namespace Database\Factories;

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
            'type'      => 'sales_point',
            'is_active' => true,
        ];
    }

    public function central(): self
    {
        return $this->state(fn() => [
            'name' => 'Central Kitchen',
            'type' => 'central',
        ]);
    }

    public function salesPoint(): self
    {
        return $this->state(fn() => [
            'type' => 'sales_point',
        ]);
    }
}
