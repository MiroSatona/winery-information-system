<?php
//FILE:     TaskType.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Enumeration of task types.

declare(strict_types=1);

namespace App\Model\Task;

/**
 * Task types
 */
enum TaskType: string 
{
    case TREATMENT = "TREATMENT";
    case HARVEST = "HARVEST";
    case BOTTLING = "BOTTLING";
};
