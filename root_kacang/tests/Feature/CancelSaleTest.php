<?php

namespace Tests\Feature;

use App\Domain\Sales\Data\CreateSaleData;
use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\BusinessDay;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\SaleRepository;
use App\Services\SaleService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_cancel_confirmed_sale_and_release_reserve()
    {
        $this->setUpLocations();
        $user = User::factory()->asRole(UserRole::OPERATOR)->create();
        $this->assignUserToLocation($user, $this->salesPoint);

        $product = Product::factory()->create();
        $production = Production::factory()->create();

        // Seed physical stock
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $this->salesPoint->id,
            'type'           => ProductTransactionType::IN,
            'quantity'       => 10,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $production->id,
            'date'           => now(),
        ]);

        $data = CreateSaleData::draft(
            invoiceNumber: 'INV-TEST-001',
            userId: $user->id,
            locationId: $this->salesPoint->id,
            businessDayId: null,
        );

        $repo = app(SaleRepository::class);
        $sale = $repo->createDraft($data);

        $sale->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 4,
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


        $this->assertEquals(4, $product->reservedAt($this->salesPoint));

        app(SaleService::class)->cancel($sale);

        $sale->refresh();

        $this->assertEquals(SaleStatus::CANCELLED, $sale->status);
        $this->assertEquals(0, $product->reservedAt($this->salesPoint));
        $this->assertEquals(10, $product->stockAt($this->salesPoint));
    }

    public function test_it_cannot_cancel_settled_sale(): void
    {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::SETTLED,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('CANCEL_INVALID_STATE');

        app(SaleService::class)->cancel($sale);
    }
}
