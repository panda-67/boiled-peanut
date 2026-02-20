<?php

namespace App\Domain\Sales\Data;

use Carbon\Carbon;

final class CreateSaleData
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly Carbon $saleDate,
        public readonly string $subtotal,
        public readonly string $discount,
        public readonly string $tax,
        public readonly string $total,
        public readonly string $userId,
        public readonly ?string $paymentStatus,
        public readonly ?string $paymentMethod,
        public readonly int $locationId,
        public readonly ?int $businessDayId,
    ) {}

    public function toPersistenceArray(): array
    {
        return [
            'invoice_number'   => $this->invoiceNumber,
            'sale_date'        => $this->saleDate,
            'subtotal'         => $this->subtotal,
            'discount'         => $this->discount,
            'tax'              => $this->tax,
            'total'            => $this->total,
            'payment_status'   => $this->paymentStatus,
            'payment_method'   => $this->paymentMethod,
            'user_id'          => $this->userId,
            'location_id'      => $this->locationId,
            'business_day_id'  => $this->businessDayId,
        ];
    }

    public static function draft(
        string $invoiceNumber,
        string $userId,
        int $locationId,
        ?int $businessDayId,
        string $subtotal = '0.00',
        string $discount = '0.00',
        string $tax = '0.00',
        string $total = '0.00',
        ?string $paymentMethod = null,
    ): self {
        return new self(
            invoiceNumber: $invoiceNumber,
            saleDate: now(),
            subtotal: $subtotal,
            discount: $discount,
            tax: $tax,
            total: $total,
            paymentStatus: null,
            paymentMethod: $paymentMethod,
            userId: $userId,
            locationId: $locationId,
            businessDayId: $businessDayId,
        );
    }
}
