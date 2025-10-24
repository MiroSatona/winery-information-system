<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use Nette\Database\Explorer;
use App\Model\Task\Treatment\Treatment;
use App\Model\Task\TaskState;


class TreatmentRepository
{   /** @var Explorer $database Database explorer. */
    private Explorer $database;

    /**
     * Constructor of the TreatmentRepository.
     *
     * @param Explorer $database Database explorer.
     */
    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }
    

    /**
     * fetchTreatments
     * Method to fetch treatments based on pattern and state.
     *
     * @param string $pattern Pattern to search for.
     * @param string $state State to filter by.
     * @return Treatment[] Array of Treatment objects.
     */
    public function fetchTreatments(string $pattern, string $state): array{

        // Fetch joined teble of tasks and treatments
        $mainQuery = $this->database->table('TREATMENT')
                        ->joinWhere('TASK', 'TASK.ID = ID_TASK')
                        ->select('TASK.*, TREATMENT.*');

        // Filter by pattern and state
        if ($pattern !== '') {
            $mainQuery->where('name LIKE ?', "%$pattern%");
        }

        if ($state !== '') {
            $mainQuery->where('state = ?', $state);
        }

        // Fetch all filtered data
        $mainQuery->fetchAll();

        // Create treatment objects and return them
        $treatments = [];
        foreach ($mainQuery as $row) {
            $treatments[] = new Treatment(
                (int) $row->ID,
                (string) $row->NAME,
                TaskState::from((string) $row->STATE),
                $row->CREATION_TIMESTAMP,
                $row->LAST_CHANGE_TIMESTAMP,
                $row->PLANNED_EXECUTION_TIMESTAMP ?? null,
                $row->EXECUTION_TIMESTAMP ?? null,
                (string) $row->NOTE,
                $row->ID_TASK_ISSUER !== null ? (int) $row->ID_TASK_ISSUER : null,
                $row->ID_TASK_CONFIRMER !== null ? (int) $row->ID_TASK_CONFIRMER : null,
                (string) $row->CATEGORY,
                (string) $row->NAME,
                (float) $row->QUANTITY,
                (string) $row->UNIT
            );
        }

        return $treatments;
    }

    /**
     * fetchById
     * Method for fetch the joined treatment with task by id.
     * @param int Id of task/bottling wich will be fetch.
     * @return Treatment|null Object of treatment if this data exists, else return null.
     */
    public function fetchById(int $id): ?Treatment
    {
    /** @var Row|null $queryResult Result of the query for joined table task and treatment */
    $queryResult = $this->database->table('TREATMENT')
        ->joinWhere('TASK', 'TASK.ID = ID_TASK')
        ->select('TASK.*, TREATMENT.*')
        ->where('TREATMENT.ID', $id)
        ->fetch();

    // If this task does not exist
    if (!$queryResult) {
        return null;
    }

    return new Treatment(
        (int) $queryResult->ID,
        (string) $queryResult->NAME,
        TaskState::from((string) $queryResult->STATE),
        $queryResult->CREATION_TIMESTAMP,
        $queryResult->LAST_CHANGE_TIMESTAMP,
        $queryResult->PLANNED_EXECUTION_TIMESTAMP ?? null,
        $queryResult->EXECUTION_TIMESTAMP ?? null,
        (string) $queryResult->NOTE,
        $queryResult->ID_TASK_ISSUER !== null ? (int) $queryResult->ID_TASK_ISSUER : null,
        $queryResult->ID_TASK_CONFIRMER !== null ? (int) $queryResult->ID_TASK_CONFIRMER : null,
        (string) $queryResult->CATEGORY,
        (string) $queryResult->NAME,
        (float) $queryResult->QUANTITY,
        (string) $queryResult->UNIT
    );
    }


}
