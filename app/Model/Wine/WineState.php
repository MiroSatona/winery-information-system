<?php

declare(strict_types=1);

namespace App\Model\Wine;

enum WineState: string
{
    case PRODUCTION = "PRODUCTION";
    case LIQUIDATED = "LIQUIDATED";
    case BOTTLED = "BOTTLED";
    case SALE = "SALE";
    case SOLD = "SOLD";
}
