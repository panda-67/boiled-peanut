<?php

namespace Tests\Feature;

use App\Domain\Inventory\ReferenceType;
use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductTransaction;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ConfirmSaleService;
use App\Services\ProductionService;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DailyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_daily_flow_from_production_to_settlement()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->central);
        $this->actingAs($user);

        // 1. Product & material
        $product = Product::factory()->create();

        $material = Material::factory()->create([
            'is_stocked' => true,
            'default_unit_cost' => 100,
        ]);

        StockMovement::create([
            'material_id' => $material->id,
            'location_id' => $this->central->id,
            'quantity' => 10,
            'type' => 'in',
            'reference_type' => ReferenceType::INITIAL,
            'reference_id' => 1,
        ]);

        // 2. Production
        $production = Production::create([
            'product_id' => $product->id,
            'output_quantity' => 14,
            'status' => 'draft',
            'date' => now(),
        ]);

        $production->materials()->attach($material->id, [
            'quantity_used' => 5,
            'unit_cost' => 100,
            'total_cost' => 500,
        ]);

        app(ProductionService::class)->execute($production);

        $this->assertEquals(14, $product->stockAt($this->central));

        // Transfer dari central ke sale point
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->central->id,
            'type'           => 'out',
            'quantity'       => -5,
            'reference_type' => ReferenceType::TRANSFER,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        // Seed stok di SALE POINT (hasil transfer, bukan produksi langsung)
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'in',
            'quantity'       => 5,
            'reference_type' => ReferenceType::TRANSFER,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        $this->assertEquals(9, $product->stockAt($this->central));
        $this->assertEquals(5, $product->stockAt($this->salesPoint));

        // 3. Sale (daily order)
        $sale = Sale::factory()->create([
            'location_id' => $this->salesPoint->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'location_id' => $this->salesPoint->id,
            'quantity' => 3,
            'unit_price' => 10000,
            'total_price' => 30000,
        ]);

        app(ConfirmSaleService::class)->confirm($sale);

        $sale->refresh();

        $this->assertEquals('confirmed', $sale->status);
        $this->assertEquals(2, $product->stockAt($this->salesPoint));
        $this->assertEquals(9, $product->stockAt($this->central));

        // 4. Settlement
        app(SettlementService::class)->settle($sale, 30000);

        $sale->refresh();

        $this->assertEquals('settled', $sale->status);

        // Assert: settlement created
        $this->assertDatabaseHas('settlements', [
            'sale_id'         => $sale->id,
            'amount_received' => 30000,
        ]);
    }
}
