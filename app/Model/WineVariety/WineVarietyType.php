<?php
//FILE:     WineVarietyType.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing types of wine varieties by grape color

declare(strict_types=1);

namespace App\Model\WineVariety;

/**
 * Wine Variety based on grape colors
 */
enum WineVarietyType: string
{
    case WHITE = 'WHITE';
    case RED = 'RED';
    case PINK = 'PINK';
    case GREY = 'GREY';
    case BLUE = 'BLUE';
}
