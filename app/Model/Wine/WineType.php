<?php
//FILE:     WineType.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing wine types by sweetness

declare(strict_types=1);

namespace App\Model\Wine;

/**
 * Wine types enum
 */
enum WineType: string
{
    case DRY = 'DRY';
    case HALF_DRY = 'HALF_DRY';
    case HALF_SWEET = 'HALF_SWEET';
    case SWEET = 'SWEET';
}
