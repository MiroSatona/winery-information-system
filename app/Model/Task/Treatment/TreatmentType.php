<?php

//FILE:     TreatmentType.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enumeration of treatment types.

declare(strict_types=1);

namespace App\Model\Task\Treatment;

/**
 * Treatment types
 */
enum TreatmentType: string
{
    case SPRAYING = 'SPRAYING';
    case FERTILIZING = 'FERTILIZING';
    case CUTTING = 'CUTTING';
    case WEEDING = 'WEEDING';
    case OTHER = 'OTHER';
}
