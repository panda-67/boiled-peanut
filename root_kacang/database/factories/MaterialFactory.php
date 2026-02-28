<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'unit' => 'kg',
            'is_stocked' => true,
            'default_unit_cost' => $this->faker->numberBetween(10000, 20000),
        ];
    }

    public function notStocked(): static
    {
        return $this->state(fn() => [
            'is_stocked' => false,
        ]);
    }
}
