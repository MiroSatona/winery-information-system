<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use Nette\Database\Explorer;
use App\Model\Task\Treatment\Treatment;


class TreatmentRepository
{
    private Explorer $database;

    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }
    

    public function fetchTreatments(string $pattern, string $state): array{
        $mainQuery = $this->database->table('TASK');

        if ($pattern !== '') {
            $mainQuery->where('name LIKE ?', "%$pattern%");
        }

        if ($state !== '') {
            $mainQuery->where('state = ?', $state);
        }


        foreach ($mainQuery as $task) {
            echo 'Task: ' . $task->name;
            echo 'State: ' . $task->state;
        }

        


        return $treatments;
    }
}
