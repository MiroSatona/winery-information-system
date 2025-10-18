<?php

namespace App\Model\OrderItem;

use App\Model\Wine\Wine;

final class OrderItem
{
    public function __construct(
        public int $idOrder,
        public Wine $wine,
        public int $bottlesCount,
        public float $pricePerBottle
    ) {}
}
