<?php
//FILE:     BottlingRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Repository for managing Bottling tasks.

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use Nette\Database\Explorer;
use App\Model\Task\TaskState;
use App\Model\Wine\Wine;
use App\Model\Task\Bottling\Bottling;
use App\Model\Wine\WineRepository;

/**
 * Handles data operations for Bottling tasks
 */
class BottlingRepository
{
    private Explorer $database;
    private WineRepository $wineRepository;

    /**
     * Constructor of the BottlingRepository.
     *
     * @param Explorer $database Database explorer.
     * @param WineRepository $wineRepository Wine repository.
     */
    public function __construct(Explorer $database, WineRepository $wineRepository)
    {
        $this->database = $database;
        $this->wineRepository = $wineRepository;
    }

    /**
     * fetchById
     * Method for fetch the joined bottling with task by id.
     * @param int id of task/bottling wich will be fetch.
     * @return Bottling|null Object of bottling if this data exists, else return null.
     */
    public function fetchById(int $id): ?Bottling
    {
        $row = $this->database->table('BOTTLING')
            ->where('ID', $id)
            ->joinWhere('TASK', 'TASK.ID = ID_TASK')
            ->select('BOTTLING.*, TASK.*')
            ->fetch();

        if ($row === null) {
            return null;
        }

        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchById((int)$row->ID_WINE);
        if ($wine === null) {
            return null;
        }

        /** @var Bottling $bottling */
        $bottling = new Bottling(
            $row->ID,
            $row->NAME,
            TaskState::from((string)$row->STATE),
            $row->CREATION_TIMESTAMP,
            $row->LAST_CHANGE_TIMESTAMP,
            $row->PLANNED_EXECUTION_TIMESTAMP,
            $row->EXECUTION_TIMESTAMP,
            $wine,
            $row->NOTE,
            ($row->ID_TASK_ISSUER !== null) ? (int)$row->ID_TASK_ISSUER : 0,
            ($row->ID_TASK_CONFIRMER !== null) ? (int)$row->ID_TASK_CONFIRMER : 0,
            ($row->ID_WINE !== null) ? (int)$row->ID_WINE : null,
            (int)$row->BOTTLES_COUNT,
            (float)$row->BOTTLE_VOLUME
        );

        return $bottling;
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
        // fetch joined table of tasks and bottlings
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
            /** @var Wine $wine */
            $wine = $this->wineRepository->fetchById((int)$row->ID_WINE);
            if ($wine === null) {
                continue;
            }
            $bottlings[] = new Bottling(
                (int)$row->ID,
                (string)$row->NAME,
                TaskState::from((string)$row->STATE),
                $row->CREATION_TIMESTAMP,
                $row->LAST_CHANGE_TIMESTAMP,
                $row->PLANNED_EXECUTION_TIMESTAMP ?? null,
                $row->EXECUTION_TIMESTAMP ?? null,
                $wine,
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
     * Get unfinished bottling task for a specific wine
     *
     * @param int $wineId
     * @return Bottling|null
     */
    public function getUnfinishedBottlingTaskForWine(int $wineId): ?Bottling
    {
        // fetch joined table of tasks and bottlings
        $row = $this->database->table('BOTTLING')
            ->where('ID_WINE', $wineId)
            ->joinWhere('TASK', 'TASK.ID = ID_TASK')
            ->select('BOTTLING.*, TASK.*')
            ->where('TASK.STATE = ?', TaskState::PLANNED->value)
            ->fetch();

        if ($row === null) {
            return null;
        }

        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchById((int)$row->ID_WINE);
        if ($wine === null) {
            return null;
        }

        /** @var Bottling $bottling */
        $bottling = new Bottling(
            $row->ID,
            $row->NAME,
            TaskState::from((string)$row->STATE),
            $row->CREATION_TIMESTAMP,
            $row->LAST_CHANGE_TIMESTAMP,
            $row->PLANNED_EXECUTION_TIMESTAMP,
            $row->EXECUTION_TIMESTAMP,
            $wine,
            $row->NOTE,
            ($row->ID_TASK_ISSUER !== null) ? (int)$row->ID_TASK_ISSUER : 0,
            ($row->ID_TASK_CONFIRMER !== null) ? (int)$row->ID_TASK_CONFIRMER : 0,
            ($row->ID_WINE !== null) ? (int)$row->ID_WINE : null,
            (int)$row->BOTTLES_COUNT,
            (float)$row->BOTTLE_VOLUME
        );

        return $bottling;
    }
}
