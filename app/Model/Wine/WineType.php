<?php

declare(strict_types=1);

namespace App\Model\Wine;

enum WineType: string
{
    case DRY = 'DRY';
    case HALF_DRY = 'HALF_DRY';
    case HALF_SWEET = 'HALF_SWEET';
    case SWEET = 'SWEET';
}
