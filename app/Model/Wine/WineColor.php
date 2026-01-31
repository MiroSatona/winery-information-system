<?php

//FILE:     WineColor.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing wine colors

declare(strict_types=1);

namespace App\Model\Wine;

/**
 * Wine colors enum
 */
enum WineColor: string
{
    case WHITE = 'WHITE';
    case RED = 'RED';
    case ROSE = 'ROSE';
}
