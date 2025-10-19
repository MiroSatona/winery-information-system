<?php

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItem;

final class CustomerOrder
{
    public function __construct(
        public int $id,
        public ?int $idCustomer,
        public float $totalPrice,
        public string $state,           // RESERVED | PAID | CANCELLED
        public ?string $deliveryType,   // PICKUP | COURIER | POST | NULL
        public string $orderTimestamp,  // store as string (from DB)
        public string $deliveryData,
        /** @var OrderItem[] */
        public array $items = []        // optional list of related items
    ) {}
}
