<?php

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItem;
use App\Model\User\User;

final class CustomerOrder
{
    private User $user;

    public function __construct(
        public int $id,
        public ?int $idCustomer,
        public float $totalPrice,
        public string $state,           // RESERVED | PAID | CANCELLED
        public ?string $deliveryType,   // PICKUP | COURIER | POST | NULL
        public string $orderTimestamp,  // store as string (from DB)
        public string $deliveryData,
        /** @var OrderItem[] */
        public array $items = [],        // optional list of related items
    ) {}
    
    // Setter for user
    public function setUser(User $user) {
        $this->user = $user;    
    }
    
    // Getter for user
    public function getUser()  {
        return $this->user;
    }
}
