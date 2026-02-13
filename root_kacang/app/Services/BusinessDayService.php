<?php

namespace App\Services;

use App\Models\BusinessDay;
use App\Models\Sale;
use App\Enums\BusinessDayStatus;
use App\Enums\SaleStatus;
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

    public function close(int $locationId, string $userId): BusinessDay
    {
        return DB::transaction(function () use ($locationId, $userId) {

            $businessDay = BusinessDay::where('location_id', $locationId)
                ->where('status', BusinessDayStatus::OPEN)
                ->lockForUpdate()
                ->first();

            if (!$businessDay) {
                throw new DomainException('NO_ACTIVE_BUSINESS_DAY');
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

            $businessDay->update([
                'status'    => BusinessDayStatus::CLOSED,
                'closed_at' => now(),
                'closed_by'  => $userId,
            ]);

            return $businessDay;
        });
    }
}
