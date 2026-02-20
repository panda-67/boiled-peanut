<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\BusinessDayService;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use App\Models\Sale;
use App\Enums\BusinessDayStatus;
use App\Enums\SaleStatus;
use DomainException;

class BusinessDayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessDayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BusinessDayService::class);
    }

    public function test_it_can_open_business_day()
    {
        $location = Location::factory()->create();
        $user = User::factory()->create();

        $day = $this->service->open($location->id, $user->id);

        $this->assertDatabaseHas('business_days', [
            'id'          => $day->id,
            'location_id' => $location->id,
            'status'      => BusinessDayStatus::OPEN,
            'opened_by'   => $user->id,
        ]);
    }

    public function test_it_cannot_open_if_already_open()
    {
        $location = Location::factory()->create();
        $user = User::factory()->create();

        BusinessDay::factory()->create([
            'location_id' => $location->id,
            'status'      => BusinessDayStatus::OPEN,
        ]);

        $this->expectException(DomainException::class);

        $this->service->open($location->id, $user->id);
    }

    public function test_it_can_close_business_day_if_no_unsettled_sales()
    {
        $location = Location::factory()->create();
        $user = User::factory()->create();

        $day = BusinessDay::factory()->create([
            'location_id' => $location->id,
            'status'      => BusinessDayStatus::OPEN,
            'opened_by'   => $user->id,
        ]);

        $closed = $this->service->close($location->id, $user->id);

        $this->assertDatabaseHas('business_days', [
            'id'        => $day->id,
            'status'    => BusinessDayStatus::CLOSED,
            'closed_by' => $user->id,
        ]);

        $this->assertNotNull($closed->closed_at);
    }

    public function test_it_cannot_close_if_unsettled_sales_exist()
    {
        $location = Location::factory()->create();
        $user = User::factory()->create();

        $day = BusinessDay::factory()->create([
            'location_id' => $location->id,
            'status'      => BusinessDayStatus::OPEN,
            'opened_by'   => $user->id,
        ]);

        Sale::factory()->create([
            'business_day_id' => $day->id,
            'status'          => SaleStatus::CONFIRMED,
        ]);

        $this->expectException(DomainException::class);

        $this->service->close($location->id, $user->id);
    }
}
