<?php

namespace Tests\Feature;

use App\Domain\Inventory\ReferenceType;
use App\Models\Product;
use App\Models\ProductTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_settle_confirmed_sale()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->salesPoint);
        $this->actingAs($user);

        // Arrange: product
        $product = Product::factory()->create();

        // Seed stock via ledger
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'in',
            'quantity'       => 10,
            'reference_type' => ReferenceType::TRANSFER,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        // Sale confirmed
        $sale = Sale::factory()->create([
            'location_id' => $this->salesPoint->id,
            'status'   => 'confirmed',
            'sale_date'     => now()->toDateString(),
            'subtotal' => 50000,
            'total'    => 50000,
        ]);

        // Sale item (historical, already confirmed)
        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 10000,
            'total_price' => 50000,
        ]);

        // Act
        $settlement = app(SettlementService::class)
            ->settle($sale, 50000);

        $sale->refresh();

        // Assert: settlement created
        $this->assertDatabaseHas('settlements', [
            'sale_id'         => $sale->id,
            'amount_received' => 50000,
            'method'          => 'warung',
        ]);

        // Assert: sale status updated
        $this->assertEquals('settled', $sale->status);

        // Assert: settlement relation exists
        $this->assertNotNull($sale->settlement);

        // Assert: stock unchanged
        $this->assertEquals(10, $product->stock());
    }
}
