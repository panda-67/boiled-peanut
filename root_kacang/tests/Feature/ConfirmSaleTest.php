<?php

namespace Tests\Feature;

use App\Domain\Inventory\ReferenceType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ProductTransaction;
use App\Models\User;
use App\Services\ConfirmSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_sale_and_reduces_product_stock_at_sale_point()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->salesPoint);
        $this->actingAs($user);

        $product = Product::factory()->create();

        // Seed stok di SALE POINT (hasil transfer, bukan produksi langsung)
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'in',
            'quantity'       => 10,
            'reference_type' => ReferenceType::TRANSFER,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->create([
            'location_id' => $this->salesPoint->id,
            'status'      => 'draft',
        ]);

        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 3,
            'unit_price'  => 10000,
            'total_price' => 0,
        ]);

        // Act
        app(ConfirmSaleService::class)->confirm($sale);

        // Assert: sale item exists
        $this->assertDatabaseHas('sale_items', [
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);

        // Assert: product transaction OUT at SALE POINT
        $this->assertDatabaseHas('product_transactions', [
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'out',
            'quantity'       => -3,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
        ]);

        // Assert: stock reduced at sale point
        $this->assertEquals(7, $product->stockAt($this->salesPoint));
    }

    public function test_it_confirms_daily_sale_and_reduces_stock_at_sale_point()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->salesPoint);
        $this->actingAs($user);

        $product = Product::factory()->create();

        // Seed stok di SALE POINT
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'in',
            'quantity'       => 20,
            'reference_type' => ReferenceType::TRANSFER,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->create([
            'status'     => 'draft',
            'sale_date'  => now()->toDateString(),
            'discount'   => 0,
            'tax'        => 0,
            'location_id' => $this->salesPoint->id,
        ]);

        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 10000,
            'total_price' => 0,
        ]);

        // Act
        app(ConfirmSaleService::class)->confirm($sale);
        $sale->refresh();

        // Assert: sale finalized
        $this->assertEquals('confirmed', $sale->status);
        $this->assertEquals(50000, $sale->subtotal);
        $this->assertEquals(50000, $sale->total);
        $this->assertEquals($sale->sale_date, $sale->sale_date);

        // Assert: stock reduced ONLY at sale point
        $this->assertEquals(15, $product->stockAt($this->salesPoint));

        // Assert: ledger entry exists at sale point
        $this->assertDatabaseHas('product_transactions', [
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => 'out',
            'quantity'       => -5,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
        ]);

        // Assert: sale item total locked
        $this->assertDatabaseHas('sale_items', [
            'sale_id'     => $sale->id,
            'product_id'  => $product->id,
            'quantity'    => 5,
            'total_price' => 50000,
        ]);
    }
}
