<?php

namespace App\Repositories\Eloquent;

use App\Domain\Guards\LocationGuard;
use App\Domain\Sales\Data\CreateSaleData;
use App\Enums\BusinessDayStatus;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\SaleRepository;
use App\Services\Context\ActiveContext;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentSaleRepository implements SaleRepository
{
    public function findToday(User $user): ?Sale
    {
        $locationId = $user->location->id;

        return Sale::where('location_id', $locationId)
            ->whereIn('status', [
                SaleStatus::DRAFT,
                SaleStatus::CONFIRMED,
                SaleStatus::SETTLED,
            ])
            ->whereHas(
                'businessDay',
                fn($q) => $q->where('status', BusinessDayStatus::OPEN)
            )
            ->with('items.product')
            ->latest('id')
            ->first();
    }

    public function startToday(User $user, ActiveContext $context): Sale
    {
        LocationGuard::ensureSalePoint($user);

        return DB::transaction(function () use ($user, $context) {

            $locationId = $context->location->id;

            $existing = Sale::where('location_id', $locationId)
                ->where('business_day_id', $context->businessDay->id)
                ->with('items.product')
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status != SaleStatus::CANCELLED) {
                return $existing;
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            $data = CreateSaleData::draft(
                userId: $user->id,
                locationId: $context->location->id,
                businessDayId: $context->businessDay->id,
                invoiceNumber: $invoiceNumber,
                date: $existing?->sale_date,
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

            $sale->save();

            $this->recalculateTotals($sale);

            return $sale->fresh(['items.product']);
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

            return $sale->fresh(['items.product']);
        });
    }

    public function createDraft(CreateSaleData $data): Sale
    {
        $sale = Sale::create([
            ...$data->toPersistenceArray(),
            'status' => SaleStatus::DRAFT,
        ]);

        return $sale->fresh(['items.product']);
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

    /**
     * This method assumes it runs within an active database transaction.
     */
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
