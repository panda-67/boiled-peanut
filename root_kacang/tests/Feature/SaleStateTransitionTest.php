<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Enums\SaleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DomainException;

class SaleStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_can_move_from_draft_to_confirmed_to_settled()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        // ---- DRAFT → CONFIRMED ----
        $sale->confirm();
        $sale->save();

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertNotNull($sale->confirmed_at);
        $this->assertNull($sale->paid_at);

        // ---- CONFIRMED → SETTLED ----
        $sale->settle();
        $sale->save();

        $this->assertEquals(SaleStatus::SETTLED, $sale->status);
        $this->assertNotNull($sale->paid_at);
    }

    public function test_cannot_confirm_if_not_in_draft()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::SETTLED,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('CONFIRM_SALE_INVALID_STATE');

        $sale->confirm();
    }

    public function test_cannot_settle_if_not_confirmed()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('SETTLE_INVALID_STATE');

        $sale->settle();
    }

    public function test_sale_becomes_immutable_after_confirmed()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        $sale->confirm();
        $sale->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('SALE_IMMUTABLE_AFTER_CONFIRM');

        $sale->notes = 'Attempt to edit after confirm';
        $sale->save();
    }

    public function test_invalid_state_transition_is_rejected()
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::DRAFT,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('SETTLE_INVALID_STATE');

        // Loncat langsung DRAFT → SETTLED
        $sale->settle();
    }
}
