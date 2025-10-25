<?php

declare(strict_types=1);

namespace App\Model\Wine;

enum WineColor: string
{
    case WHITE = 'WHITE';
    case RED = 'RED';
    case ROSE = 'ROSE';
}
