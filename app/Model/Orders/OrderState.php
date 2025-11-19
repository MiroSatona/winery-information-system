<?php

declare(strict_types=1);

namespace App\Model\Orders;

enum OrderState: string
{
    case RESERVED = "RESERVED";
    case PAID = "PAID";
    case CANCELLED = "CANCELLED";
    case DELIVERED = "DELIVERED";
}

