<?php
//FILE:     OrderItem.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a single order item of an order.

namespace App\Model\OrderItem;

use App\Model\Wine\Wine;

/**
 * Represents a single Order Item of an order
 */
final class OrderItem
{
    /**
     * Order Item constructor
     *
     * @param int $idOrder
     * @param Wine $wine
     * @param int $bottlesCount
     * @param float $pricePerBottle
     */
    public function __construct(
        public int $idOrder,
        public Wine $wine,
        public int $bottlesCount,
        public float $pricePerBottle
    ) {}
}
