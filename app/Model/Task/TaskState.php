<?php
//FILE:     TaskState.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enumeration of task states.

declare(strict_types=1);

namespace App\Model\Task;

/**
 * Task states
 */
enum TaskState: string
{
    case PLANNED = "PLANNED";
    case FINISHED = "FINISHED";
    case CANCELED = "CANCELED";
};
