<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

enum TreatmentType: string
{
    case SPRAYING = 'SPRAYING';
    case FERTILIZING = 'FERTILIZING';
    case CUTTING = 'CUTTING';
    case WEEDING = 'WEEDING';
    case OTHER = 'OTHER';
}