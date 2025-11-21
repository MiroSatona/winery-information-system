<?php
//FILE:     OrderStatus.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum of state of order

declare(strict_types=1);

namespace App\Model\Orders;

/**
 * Order states
 */
enum OrderState: string
{
    case RESERVED = "RESERVED";
    case PAID = "PAID";
    case CANCELLED = "CANCELLED";
    case DELIVERED = "DELIVERED";
}
