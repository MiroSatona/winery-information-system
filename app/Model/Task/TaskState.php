<?php

declare(strict_types=1);

namespace App\Model\Task;


enum TaskState: string
{
    case PLANNED = "PLANNED";
    case FINISHED = "FINISHED";
    case CANCELED = "CANCELED";
};