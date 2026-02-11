<?php

namespace Tests\Feature;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Models\BusinessDay;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ProductTransaction;
use App\Models\User;
use App\Services\SaleService;
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

        $sale = Sale::factory()
            ->forUser($user)
            ->atLocation($this->salesPoint)
            ->create([
                'status' => SaleStatus::DRAFT,
            ]);

        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 3,
            'unit_price'  => 10000,
            'total_price' => 0,
        ]);

        BusinessDay::factory()
            ->forLocation($this->salesPoint)
            ->create(['status' => 'open',]);

        // Act
        app(SaleService::class)->confirm($sale);

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
            'type'           => ProductTransactionType::RESERVE,
            'quantity'       => -3,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
        ]);

        // Assert: stock reduced at sale point by reserve
        $this->assertEquals(-3, $product->reservedAt($this->salesPoint));
        $this->assertEquals(7, $product->availableAt($this->salesPoint));
    }

    public function test_it_confirms_daily_sale_and_reduces_stock_at_sale_point()
    {
        // Arrange
        $this->setUpLocations();

        $user = User::factory()->create();
        $this->assignUserToLocation($user, $this->salesPoint);

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

        $sale = Sale::factory()
            ->forUser($user)
            ->atLocation($this->salesPoint)
            ->create([
                'sale_date'  => now()->toDateString(),
                'status'     => SaleStatus::DRAFT,
                'discount'   => 0,
                'tax'        => 0,
            ]);

        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 10000,
            'total_price' => 0,
        ]);

        BusinessDay::factory()
            ->forLocation($this->salesPoint)
            ->onDate(now())
            ->create();

        // Act
        app(SaleService::class)->confirm($sale);
        $sale->refresh();

        // Assert: sale finalized
        $this->assertEquals(SaleStatus::CONFIRMED, $sale->status);
        $this->assertEquals(50000, $sale->subtotal);
        $this->assertEquals(50000, $sale->total);
        $this->assertEquals($sale->sale_date, $sale->sale_date);

        // Assert: stock reduced ONLY at sale point by reserve
        $this->assertEquals(-5, $product->reservedAt($this->salesPoint));
        $this->assertEquals(15, $product->availableAt($this->salesPoint));

        // Assert: ledger entry exists at sale point
        $this->assertDatabaseHas('product_transactions', [
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::RESERVE,
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
