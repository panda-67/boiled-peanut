<?php

namespace Tests\Feature;

use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\BusinessDay;
use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use App\Services\ProductionService;
use App\Services\ProductTransferService;
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

        $manager = User::factory()->asRole(UserRole::MANAGER)->create();
        $this->assignUserToLocation($manager, $this->central);


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

        $operator = User::factory()->asRole(UserRole::OPERATOR)->create();
        $this->assignUserToLocation($operator, $this->salesPoint);

        // Seed stok di SALE POINT dari CENTRAL (hasil transfer, bukan produksi langsung)
        app(ProductTransferService::class)->transfer(
            product: $product,
            from: $this->central,
            to: $this->salesPoint,
            qty: 5
        );

        $this->assertEquals(9, $product->stockAt($this->central));
        $this->assertEquals(5, $product->stockAt($this->salesPoint));

        // 3. Sale (daily order)
        $sale = Sale::factory()
            ->forUser($operator)
            ->atLocation($this->salesPoint)
            ->create([
                'status' => SaleStatus::DRAFT,
            ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'location_id' => $this->salesPoint->id,
            'quantity' => 3,
            'unit_price' => 10000,
            'total_price' => 30000,
        ]);

        BusinessDay::factory()
            ->forLocation($this->salesPoint)
            ->create(['status' => 'open',]);

        app(SaleService::class)->confirm($sale);

        $sale->refresh();

        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(2, $product->availableAt($this->salesPoint));
        $this->assertEquals(9, $product->stockAt($this->central));

        // 4. Settlement
        app(SettlementService::class)->settle($sale, 30000);

        $sale->refresh();

        $this->assertEquals(SaleStatus::SETTLED, $sale->status);

        // Assert: settlement created
        $this->assertDatabaseHas('settlements', [
            'sale_id'         => $sale->id,
            'amount_received' => 30000,
        ]);
    }
}
