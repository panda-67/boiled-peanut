<?php

namespace Tests\Feature;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductTransaction;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use App\Services\ProductStockService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalizeSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_sale_converts_reserve_to_out_and_clears_reservation()
    {
        // Arrange
        $this->setUpLocations();
        $product  = Product::factory()->create();
        $production = Production::factory()->create();

        // Physical stock +10
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::IN,
            'quantity'       => 10,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $production->id,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->confirmed()->create();

        // Reserve +4
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::RESERVE,
            'quantity'       => 4,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
            'date'           => now(),
        ]);

        $this->assertEquals(10, $product->stockAt($this->salesPoint));
        $this->assertEquals(4, $product->reservedAt($this->salesPoint));
        $this->assertEquals(6, $product->availableAt($this->salesPoint));

        // Act
        app(ProductStockService::class)->finalizeSale($sale);

        // Refresh model state
        $product->refresh();

        // Assert ledger math
        $this->assertEquals(6, $product->stockAt($this->salesPoint));
        $this->assertEquals(0, $product->reservedAt($this->salesPoint));
        $this->assertEquals(6, $product->availableAt($this->salesPoint));

        // Assert OUT exists (negative)
        $this->assertDatabaseHas('product_transactions', [
            'reference_id' => $sale->id,
            'type'         => ProductTransactionType::OUT,
            'quantity'     => -4,
        ]);

        // Assert reserve reversal exists
        $this->assertDatabaseHas('product_transactions', [
            'reference_id' => $sale->id,
            'type'         => ProductTransactionType::RESERVE,
            'quantity'     => -4,
        ]);
    }

    public function test_cancel_releases_reserve_without_affecting_physical_stock()
    {
        // Arrange
        $this->setUpLocations();
        $product  = Product::factory()->create();
        $production = Production::factory()->create();

        // Physical +10
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::IN,
            'quantity'       => 10,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $production->id,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->create([
            'location_id'   => $this->salesPoint->id,
            'status'        => SaleStatus::CONFIRMED,
        ]);

        // Reserve +4
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::RESERVE,
            'quantity'       => 4,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
            'date'           => now(),
        ]);

        $this->assertEquals(10, $product->stockAt($this->salesPoint));
        $this->assertEquals(4, $product->reservedAt($this->salesPoint));
        $this->assertEquals(6, $product->availableAt($this->salesPoint));

        app(SaleRepository::class)->cancel($sale->id);

        // Act
        app(ProductStockService::class)->releaseReservation($sale);

        $product->refresh();

        // Assert
        $this->assertEquals(10, $product->stockAt($this->salesPoint));
        $this->assertEquals(0, $product->reservedAt($this->salesPoint));
        $this->assertEquals(10, $product->availableAt($this->salesPoint));

        // Ensure no OUT was written
        $this->assertDatabaseMissing('product_transactions', [
            'reference_id' => $sale->id,
            'type'         => ProductTransactionType::OUT,
        ]);

        // Ensure reserve reversal exists
        $this->assertDatabaseHas('product_transactions', [
            'reference_id' => $sale->id,
            'type'         => ProductTransactionType::RESERVE,
            'quantity'     => -4,
        ]);
    }

    public function test_finalize_cannot_be_called_twice()
    {
        $this->setUpLocations();
        $product  = Product::factory()->create();
        $production = Production::factory()->create();

        // Physical stock +10
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::IN,
            'quantity'       => 10,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $production->id,
            'date'           => now(),
        ]);

        $sale = Sale::factory()->confirmed()->create();

        // Reserve +4
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::RESERVE,
            'quantity'       => 4,
            'reference_type' => ReferenceType::SALE,
            'reference_id'   => $sale->id,
            'date'           => now(),
        ]);

        $this->expectException(DomainException::class);
        $stockService = app(ProductStockService::class);

        $stockService->finalizeSale($sale);
        $stockService->finalizeSale($sale); // should explode
    }
}
