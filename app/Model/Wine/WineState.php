<?php
//FILE:     WineState.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing wine states (production - sale)

declare(strict_types=1);

namespace App\Model\Wine;

/**
 * Wine state enum
 */
enum WineState: string
{
    case PRODUCTION = "PRODUCTION";
    case LIQUIDATED = "LIQUIDATED";
    case BOTTLED = "BOTTLED";
    case SALE = "SALE";
    case SOLD = "SOLD";
}
