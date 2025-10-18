<?php

declare(strict_types=1);

namespace App\Model\Wine;

enum WineState: string
{
    case production = "PRODUCTION";
    case liquidated = "LIQUIDATED";
    case bottled = "BOTTLED";
    case sale = "SALE";
    case sold = "SOLD";
}

