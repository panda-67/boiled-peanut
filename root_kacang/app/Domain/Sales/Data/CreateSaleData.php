<?php

namespace App\Domain\Sales\Data;

use Illuminate\Support\Carbon;

final class CreateSaleData
{
    public function __construct(
        public readonly string $userId,
        public readonly int $locationId,
        public readonly ?int $businessDayId,
        public readonly string $invoiceNumber,
        public readonly Carbon $saleDate,
        public readonly string $subtotal,
        public readonly string $discount,
        public readonly string $tax,
        public readonly string $total,
        public readonly ?string $paymentMethod,
    ) {}

    public function toPersistenceArray(): array
    {
        return [
            'user_id'          => $this->userId,
            'location_id'      => $this->locationId,
            'business_day_id'  => $this->businessDayId,
            'invoice_number'   => $this->invoiceNumber,
            'sale_date'        => $this->saleDate,
            'subtotal'         => $this->subtotal,
            'discount'         => $this->discount,
            'tax'              => $this->tax,
            'total'            => $this->total,
            'payment_method'   => $this->paymentMethod,
        ];
    }

    public static function draft(
        string $userId,
        int $locationId,
        ?int $businessDayId,
        string $invoiceNumber,
        ?Carbon $date = null,
        string $subtotal = '0.00',
        string $discount = '0.00',
        string $tax = '0.00',
        string $total = '0.00',
        ?string $paymentMethod = null,
    ): self {
        return new self(
            userId: $userId,
            locationId: $locationId,
            businessDayId: $businessDayId,
            invoiceNumber: $invoiceNumber,
            saleDate: $date ?? now(),
            subtotal: $subtotal,
            discount: $discount,
            tax: $tax,
            total: $total,
            paymentMethod: $paymentMethod,
        );
    }
}
