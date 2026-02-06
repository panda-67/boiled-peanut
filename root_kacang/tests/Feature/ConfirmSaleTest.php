<?php

namespace Tests\Feature;

use App\Domain\Inventory\ReferenceType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ProductTransaction;
use App\Services\ConfirmSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_sale_and_reduces_product_stock_via_transaction()
    {
        // Arrange
        $product = Product::factory()->create();

        // seed stok lewat ledger, BUKAN kolom stock
        ProductTransaction::create([
            'product_id'     => $product->id,
            'type'           => 'in',
            'quantity'       => 10,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->create();

        $sale->items()->create([
            'product_id'    => $product->id,
            'quantity'      => 3,
            'unit_price'    => 10000,
            'total_price'   => 30000,
        ]);

        // Act
        app(ConfirmSaleService::class)->confirm($sale);

        // Assert: sale item created
        $this->assertDatabaseHas('sale_items', [
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);

        // Assert: product transaction OUT created
        $this->assertDatabaseHas('product_transactions', [
            'product_id'     => $product->id,
            'type'           => 'out',
            'quantity'       => -3,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
        ]);

        // Assert: stock reduced correctly (ledger-based)
        $this->assertEquals(7, $product->stock());
    }

    public function test_it_confirms_daily_sale_and_reduces_stock()
    {
        // Arrange: product
        $product = Product::factory()->create();

        // Seed stock via ledger (production result)
        ProductTransaction::create([
            'product_id'     => $product->id,
            'type'           => 'in',
            'quantity'       => 20,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => 1,
            'date'           => now(),
        ]);

        // Sale harian (draft)
        $sale = Sale::factory()->create([
            'status' => 'draft',
            'sale_date'   => now()->toDateString(),
            'discount' => 0,
            'tax' => 0,
        ]);

        // Sale item (1 produk)
        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 10000,
            'total_price' => 0, // akan dikunci saat confirm
        ]);

        // Act
        app(ConfirmSaleService::class)->confirm($sale);

        $sale->refresh();

        // Assert: status & total
        $this->assertEquals('confirmed', $sale->status);
        $this->assertEquals(50000, $sale->subtotal);
        $this->assertEquals(50000, $sale->total);
        $this->assertEquals($sale->sale_date, $sale->sale_date);

        // Assert: product stock reduced via ledger
        $this->assertEquals(15, $product->stock());

        // Assert: product transaction OUT exists
        $this->assertDatabaseHas('product_transactions', [
            'product_id'     => $product->id,
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
