<?php

namespace Tests\Feature;

use App\Domain\Inventory\ReferenceType;
use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_execute_production_flow_correctly()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->central);
        $this->actingAs($user);


        // Product
        $product = Product::factory()->create();

        // Materials
        $materialA = Material::factory()->create([
            'is_stocked' => true,
            'default_unit_cost' => 100,
        ]);

        $materialB = Material::factory()->create([
            'is_stocked' => true,
            'default_unit_cost' => 50,
        ]);

        // Initial material stock (ledger)
        StockMovement::create([
            'material_id' => $materialA->id,
            'location_id' => $this->central->id,
            'quantity' => 10,
            'type' => 'in',
            'reference_type' => ReferenceType::INITIAL,
            'reference_id' => 1,
        ]);

        StockMovement::create([
            'material_id' => $materialB->id,
            'location_id' => $this->central->id,
            'quantity' => 20,
            'type' => 'in',
            'reference_type' => ReferenceType::INITIAL,
            'reference_id' => 1,
        ]);

        // Production (draft)
        $production = Production::create([
            'date' => now(),
            'product_id' => $product->id,
            'output_quantity' => 5,
            'status' => 'draft',
        ]);

        // Attach materials
        $production->materials()->attach($materialA->id, [
            'quantity_used' => 2,
            'unit_cost' => $materialA->default_unit_cost,
            'total_cost' => $materialA->default_unit_cost * 2,
        ]);

        $production->materials()->attach($materialB->id, [
            'quantity_used' => 4,
            'unit_cost' => $materialB->default_unit_cost,
            'total_cost' => $materialB->default_unit_cost * 4,
        ]);

        app(ProductionService::class)->execute($production);

        $production->refresh();

        $this->assertEquals('completed', $production->status);
        $this->assertEquals(400, $production->total_cost); // 100 * 2 + 50 * 4 = 400

        $this->assertEquals(8, $materialA->stockAt($this->central)); // 10 - 2 = 8
        $this->assertEquals(16, $materialB->stockAt($this->central)); // 20 - 4 = 16
        $this->assertEquals(5, $product->stock());

        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $materialA->id,
            'quantity' => -2,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id' => $production->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $materialB->id,
            'quantity' => -4,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id' => $production->id,
        ]);

        $this->assertDatabaseHas('product_transactions', [
            'product_id' => $product->id,
            'quantity' => 5,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id' => $production->id,
        ]);
    }
}
