<?php

declare(strict_types=1);

namespace App\Model\Task;

enum TaskType: string 
{
    case TREATMENT = "TREATMENT";
    case HARVEST = "HARVEST";
    case BOTTLING = "BOTTLING";
};