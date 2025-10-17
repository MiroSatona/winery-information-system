<?php

declare(strict_types=1);

namespace App\Model\Orders;

enum OrderState: string
{
    case reserved = "RESERVED";
    case paid = "PAID";
    case cancelled = "CANCELLED";
}

