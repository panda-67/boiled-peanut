<?php

namespace App\Services;

use App\Models\BusinessDay;
use App\Models\Sale;
use App\Enums\BusinessDayStatus;
use App\Enums\LocationType;
use App\Enums\SaleStatus;
use App\Models\DailyClosing;
use App\Models\DailyCogs;
use App\Models\SaleItem;
use App\Models\Settlement;
use DomainException;
use Illuminate\Support\Facades\DB;

class BusinessDayService
{
    public function open(int $locationId, string $userId): BusinessDay
    {
        return DB::transaction(function () use ($locationId, $userId) {

            $existing = BusinessDay::where('location_id', $locationId)
                ->where('status', BusinessDayStatus::OPEN)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new DomainException('BUSINESS_DAY_ALREADY_OPEN');
            }

            return BusinessDay::create([
                'location_id' => $locationId,
                'date'        => today(),
                'opened_at'   => now(),
                'opened_by'   => $userId,
                'status'      => BusinessDayStatus::OPEN,
            ]);
        });
    }

    public function close(int $locationId, string $userId, ?int $receivedCash): BusinessDay
    {
        return DB::transaction(function () use ($locationId, $userId, $receivedCash) {

            $businessDay = BusinessDay::where('location_id', $locationId)
                ->where('status', BusinessDayStatus::OPEN)
                ->lockForUpdate()
                ->first();

            if (!$businessDay) {
                throw new DomainException('NO_ACTIVE_BUSINESS_DAY');
            }

            if ($businessDay->location->type === LocationType::SALE_POINT) {
                $this->handleSalesPointClosing($businessDay, $receivedCash);
            }

            $businessDay->update([
                'status'    => BusinessDayStatus::CLOSED,
                'closed_at' => now(),
                'closed_by'  => $userId,
            ]);

            return $businessDay;
        });
    }

    private function handleSalesPointClosing(BusinessDay $businessDay, ?int $receivedCash): void
    {
        if (!$businessDay->sales()->exists()) {
            throw new DomainException('NO_SALE_TODAY');
        }

        $hasUnsettledSales = Sale::where('business_day_id', $businessDay->id)
            ->whereIn('status', [
                SaleStatus::DRAFT,
                SaleStatus::CONFIRMED,
            ])
            ->exists();

        if ($hasUnsettledSales) {
            throw new DomainException('UNSETTLED_SALES_EXIST');
        }

        if ($receivedCash) {
            $expectedCash = Settlement::whereHas('sale', function ($q) use ($businessDay) {
                $q->where('business_day_id', $businessDay->id);
            })
                ->where('method', 'cash')
                ->sum('amount_received');

            $difference = $receivedCash - $expectedCash;

            DailyClosing::create([
                'business_day_id' => $businessDay->id,
                'expected_cash'   => $expectedCash,
                'received_cash'   => $receivedCash,
                'difference'      => $difference,
            ]);

            $saleItems = SaleItem::whereHas('sale', function ($q) use ($businessDay) {
                $q->where('business_day_id', $businessDay->id)
                    ->where('status', SaleStatus::SETTLED);
            });

            $quantitySold = $saleItems->sum('quantity');
            $cogs = $saleItems->sum('total_cost');

            $averageCost = $quantitySold > 0
                ? round($cogs / $quantitySold, 4)
                : 0;

            DailyCogs::create([
                'business_day_id' => $businessDay->id,
                'quantity_sold'   => $quantitySold,
                'average_cost'    => $averageCost,
                'cogs_amount'     => $cogs,
            ]);
        }
    }
}
