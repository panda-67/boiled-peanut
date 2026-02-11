<?php

namespace Database\Factories;

use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessDay>
 */
class BusinessDayFactory extends Factory
{
    protected $model = BusinessDay::class;

    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'date'        => now()->toDateString(),
            'status'      => 'open',
            'opened_at'   => now(),
            'opened_by'   => User::factory(),
        ];
    }

    /**
     * Business day sudah ditutup
     */
    public function closed(): self
    {
        return $this->state(fn() => [
            'status'    => 'closed',
            'closed_at' => now(),
            'closed_by' => User::factory(),
        ]);
    }

    /**
     * Paksa untuk location tertentu
     */
    public function forLocation(Location $location): self
    {
        return $this->state(fn() => [
            'location_id' => $location->id,
        ]);
    }

    /**
     * Tentukan tanggal operasional
     */
    public function onDate(\Carbon\Carbon|string $date): self
    {
        return $this->state(fn() => [
            'date' => $date instanceof \Carbon\Carbon
                ? $date->toDateString()
                : $date,
        ]);
    }
}
