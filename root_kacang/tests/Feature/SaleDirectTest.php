<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Sale;
use DomainException;
use Tests\TestCase;

class SaleDirectTest extends TestCase
{
    public function test_direct_query_update_does_change_status()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        Sale::where('id', $sale->id)->update([
            'status' => SaleStatus::CONFIRMED,
        ]);

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->fresh()->status);
    }

    public function test_cannot_bypass_state_machine_via_direct_status_update()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('STATUS_MUST_BE_CHANGED_VIA_DOMAIN_METHOD');

        $sale->update([
            'status' => SaleStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }
}
