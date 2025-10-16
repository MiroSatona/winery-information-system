<?php

declare(strict_types=1);

namespace App\Model\WineVariety;

enum WineVarietyType: string
{
    case WHITE = 'WHITE';
    case RED = 'RED';
    case PINK = 'PINK';
    case GREY = 'GREY';
    case BLUE = 'BLUE';
}