<?php

namespace Database\Factories;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'code' => strtoupper(Str::random(8)),
            'unit' => $this->faker->randomElement(['kg', 'pcs', 'liter']),
            'type' => ItemType::RAW,
            'is_stocked' => true,
            'default_unit_cost' => $this->faker->randomFloat(2, 10000, 20000),
        ];
    }

    public function material(): static
    {
        return $this->state(fn() => [
            'type' => ItemType::RAW,
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn() => [
            'type' => ItemType::FINISHED,
        ]);
    }

    public function notStocked(): static
    {
        return $this->state(fn() => [
            'is_stocked' => false,
        ]);
    }
}
