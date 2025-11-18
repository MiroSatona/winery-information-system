<?php 

declare(strict_types=1);

namespace App\Model\Task;

enum TaskOperationStatus: string
{
    case FAILED = "FAILED";
    case SUCCESS = "SUCCESS";
    case CONFLICT_WITH_TREATMENT = "CONFLICT_TREATMENT";
    case CONFLICT_WITH_HARVEST = "CONFLICT_WITH_HARVEST";
};
