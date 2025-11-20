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
 * States:
 *  - RESERVED: Order has been reserved and awaits payment or confirmation.
 *  - PAID:     Payment has been received for the order.
 *  - CANCELLED: Order has been cancelled and will not be fulfilled.
 *  - DELIVERED: Order has been delivered to the customer.
 *
 */
enum OrderState: string
{
    case RESERVED = "RESERVED";
    case PAID = "PAID";
    case CANCELLED = "CANCELLED";
    case DELIVERED = "DELIVERED";
}

