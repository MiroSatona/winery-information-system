<?php

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use Nette\Database\Explorer;
use App\Model\Task\TaskState;


class BottlingRepository
{
    private Explorer $database;

    /**
     * Constructor of the BottlingRepository.
     *
     * @param Explorer $database Database explorer.
     */
    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }

    /**
     * fetchBottling
     * Method to fetch bottling tasks based on pattern and state.
     *
     * @param string $pattern Pattern to search for.
     * @param string $state State to filter by.
     * @return Bottling[] Array of Bottling objects.
     */
    public function fetchBottling(string $pattern, string $state): array
    {
        // Fetch joined teble of tasks and bottlings
        $mainQuery = $this->database->table('BOTTLING')
                        ->joinWhere('TASK', 'TASK.ID = ID_TASK')
                        ->select('TASK.*, BOTTLING.*');

        // Filter by pattern and state
        if ($pattern !== '') {
            $mainQuery->where('name LIKE ?', "%$pattern%");
        }

        if ($state !== '') {
            $mainQuery->where('state = ?', $state);
        }

        // Fetch all filtered data
        $mainQuery->fetchAll();

        // Create bottling objects and return them
        $bottlings = [];
        foreach ($mainQuery as $row) {
            $bottlings[] = new Bottling(
                (int)$row->ID,
                (string)$row->NAME,
                TaskState::from((string)$row->STATE),
                $row->CREATION_TIMESTAMP,
                $row->LAST_CHANGE_TIMESTAMP,
                $row->PLANNED_EXECUTION_TIMESTAMP ?? null,
                $row->EXECUTION_TIMESTAMP ?? null,
                (string)$row->NOTE,
                $row->ID_TASK_ISSUER !== null ? (int)$row->ID_TASK_ISSUER : null,
                $row->ID_TASK_CONFIRMER !== null ? (int)$row->ID_TASK_CONFIRMER : null,
                $row->ID_WINE !== null ? (int)$row->ID_WINE : null,
                (int)$row->BOTTLES_COUNT,
                (float)$row->BOTTLE_VOLUME
            );
        }

        return $bottlings;
    }

    /**
     * fetchById
     * Method for fetch the joined bottling with task by id.
     * @param int Id of task/bottling wich will be fetch.
     * @return Bottling|null Object of bottling if this data exists, else return null.
     */
    public function fetchById(int $id): ?Bottling
    {
        /** @var Row|null $queryResult Result of the query for joined table task and bottling */
        $queryResult = $this->database->table('BOTTLING')
            ->joinWhere('TASK', 'TASK.ID = ID_TASK')
            ->select('TASK.*, BOTTLING.*')
            ->where('BOTTLING.ID_TASK', $id)
            ->fetch();

        // If this task does not exist
        if (!$queryResult) {
            return null;
        }

        return new Bottling(
            (int)$queryResult->ID,
            (string)$queryResult->NAME,
            TaskState::from((string)$queryResult->STATE),
            $queryResult->CREATION_TIMESTAMP,
            $queryResult->LAST_CHANGE_TIMESTAMP,
            $queryResult->PLANNED_EXECUTION_TIMESTAMP ?? null,
            $queryResult->EXECUTION_TIMESTAMP ?? null,
            (string)$queryResult->NOTE,
            $queryResult->ID_TASK_ISSUER !== null ? (int)$queryResult->ID_TASK_ISSUER : null,
            $queryResult->ID_TASK_CONFIRMER !== null ? (int)$queryResult->ID_TASK_CONFIRMER : null,
            $queryResult->ID_WINE !== null ? (int)$queryResult->ID_WINE : null,
            (int)$queryResult->BOTTLES_COUNT,
            (float)$queryResult->BOTTLE_VOLUME
        );
    }

}

