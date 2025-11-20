<?php
//FILE:     WineGrade.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing wine grades

declare(strict_types=1);

namespace App\Model\Wine;

/**
 * Wine grades enum
 */
enum WineGrade: string
{
    case NONE = 'NONE';
    case CABINET = 'CABINET';
    case LATE = 'LATE';
    case GRAPE_SELECTION = 'GRAPE_SELECTION';
    case BERRY_SELECTION = 'BERRY_SELECTION';
    case ICE = 'ICE';
    case STRAW = 'STRAW';
}
