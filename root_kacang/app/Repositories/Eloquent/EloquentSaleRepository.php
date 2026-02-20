<?php

namespace App\Repositories\Eloquent;

use App\Domain\Sales\Data\CreateSaleData;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\SaleRepository;
use App\Services\Context\ActiveContextResolver;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentSaleRepository implements SaleRepository
{
    public function __construct(
        protected ActiveContextResolver $contextResolver
    ) {}

    public function findToday(User $user): ?Sale
    {
        $context = $this->contextResolver->resolveForUser($user);
        $locationId = $context->location->id;

        return Sale::with('items.product')
            ->where('sale_date', now()->toDateString())
            ->where('location_id', $locationId)
            ->whereIn('status', [
                SaleStatus::DRAFT,
                SaleStatus::CONFIRMED,
                SaleStatus::SETTLED,
            ])
            ->latest('id')
            ->first();
    }

    public function startToday(User $user): Sale
    {
        $context = $this->contextResolver->resolveForUser($user);

        return DB::transaction(function () use ($user, $context) {

            $existing = Sale::whereDate('sale_date', today())
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            $data = CreateSaleData::draft(
                invoiceNumber: $invoiceNumber,
                userId: $user->id,
                locationId: $context->location->id,
                businessDayId: $context->businessDay->id,
            );

            return $this->createDraft($data);
        });
    }

    public function addItem(string $saleId, string $productId, int $qty): Sale
    {

        return DB::transaction(function () use ($saleId, $productId, $qty): Sale {

            $sale = $this->findOrFail($saleId);
            $product = Product::find($productId);

            $existingItem = $sale->items()
                ->where('product_id', $productId)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $qty;
                $existingItem->total_price =
                    $existingItem->quantity * $existingItem->unit_price;
                $existingItem->save();
            } else {
                $sale->items()->create([
                    'product_id'  => $product->id,
                    'quantity'    => $qty,
                    'unit_price'  => $product->selling_price,
                    'total_price' => $qty * $product->selling_price
                ]);
            }

            $this->recalculateTotals($sale);

            return $sale->fresh([
                'items.product'
            ]);
        });
    }

    public function removeItem(string $saleId, string $itemId): Sale
    {
        return DB::transaction(function () use ($saleId, $itemId): Sale {

            $sale = $this->findOrFail($saleId);

            if ($sale->status !== SaleStatus::DRAFT) {
                throw new DomainException('Cannot modify non-draft sale');
            }

            $item = $sale->items()
                ->where('id', $itemId)
                ->firstOrFail();

            if ($item->quantity > 1) {
                $item->quantity -= 1;
                $item->total_price = $item->quantity * $item->unit_price;
                $item->save();
            } else {
                $item->delete();
            }

            $sale->save();

            $this->recalculateTotals($sale);

            return $sale->fresh([
                'items.product'
            ]);
        });
    }

    public function createDraft(CreateSaleData $data): Sale
    {
        return Sale::create([
            ...$data->toPersistenceArray(),
            'status' => SaleStatus::DRAFT,
        ]);
    }

    public function save(Sale $sale): void
    {
        $sale->save();
    }

    public function confirm(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->confirm();
            $sale->save();
        });
    }

    public function settle(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->settle();
            $sale->save();
        });
    }

    public function cancel(string $id): void
    {
        DB::transaction(function () use ($id) {
            $sale = $this->findOrFail($id);

            $sale->cancel();
            $sale->save();
        });
    }

    protected function recalculateTotals(Sale $sale): void
    {
        $subtotal = $sale->items()->sum('total_price');

        $sale->subtotal = $subtotal;
        $sale->tax = 0; // adjust if needed
        $sale->discount = 0;
        $sale->total = $subtotal + $sale->tax - $sale->discount;

        $sale->save();
    }

    private function findOrFail(string $id): Sale
    {
        return Sale::query()->lockForUpdate()->findOrFail($id);
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }
}
