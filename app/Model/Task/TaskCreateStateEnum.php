<?php

//FILE:     TaskCreateStateEnum.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enumeration of task creation operation statuses.

declare(strict_types=1);

namespace App\Model\Task;

/**
 * Task creation operation statuses
 */
enum TaskOperationStatus: string
{
    case FAILED = "FAILED";
    case SUCCESS = "SUCCESS";
    case CONFLICT_WITH_TREATMENT = "CONFLICT_TREATMENT";
    case CONFLICT_WITH_HARVEST = "CONFLICT_WITH_HARVEST";
};
