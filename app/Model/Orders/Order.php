<?php
//FILE:     Order.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a customer order.

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItem;
use App\Model\User\User;

/**
 * Represents a single Customer Order in the system
 */
final class CustomerOrder
{
    // owning user of this order (set separately)
    private User $user;

    /**
     * Customer Order constructor
     *
     * @param int $id
     * @param int|null $idCustomer
     * @param float $totalPrice
     * @param string $state           // RESERVED | PAID | CANCELLED
     * @param string $deliveryType   // PICKUP | COURIER | POST 
     * @param string $orderTimestamp  // store as string (from DB)
     * @param string $deliveryData
     * @param OrderItem[] $items        // optional list of related items
     */
    public function __construct(
        public int $id,
        public ?int $idCustomer,
        public float $totalPrice,
        public string $state,
        public string $deliveryType,
        public string $orderTimestamp,
        public string $deliveryData,
        /** @var OrderItem[] */
        public array $items = [],
    ) {}
    
    /**
     * Setter for user
     *
     * @param User $user
     */
    public function setUser(User $user) {
        $this->user = $user;    
    }
    
    /**
     * Getter for user
     *
     * @return User
     */
    public function getUser()  {
        return $this->user;
    }
}
