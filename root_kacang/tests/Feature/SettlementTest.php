<?php

namespace Tests\Feature;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
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

        $user   = User::factory()->asRole(UserRole::MANAGER)->create();
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
        $sale = Sale::factory()
            ->atLocation($this->salesPoint)
            ->forUser($user)
            ->confirmed()
            ->create([
                'sale_date'     => now()->toDateString(),
                'subtotal'      => 40000,
                'total'         => 40000,
            ]);

        // Sale item (historical, already confirmed)
        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 4,
            'unit_price'  => 10000,
            'total_price' => 40000,
        ]);

        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::RESERVE,
            'quantity'       => 4,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
            'date'           => now(),
        ]);

        // Act
        app(SettlementService::class)->settle($sale, 40000);

        $sale->refresh();

        // Assert: settlement created
        $this->assertDatabaseHas('settlements', [
            'sale_id'         => $sale->id,
            'amount_received' => 40000,
            'method'          => 'warung',
        ]);

        // Assert: sale status updated
        $this->assertEquals(SaleStatus::SETTLED, $sale->status);

        // Assert: settlement relation exists
        $this->assertNotNull($sale->settlement);

        // Assert: stock reduce by 4 from 10
        $this->assertEquals(6, $product->stockAt($this->salesPoint));
    }
}
