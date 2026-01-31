<?php

//FILE:     TreatmentRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for Treatment tasks.

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use Nette\Database\Explorer;
use App\Model\Task\Treatment\Treatment;
use App\Model\Task\TaskState;
use App\Model\Task\Treatment\TreatmentType;
use App\Model\Vineyard\Vineyard;
use App\Model\Vineyard\VineyardRepository;

/**
 * Handles treatment related DB operations
 */
class TreatmentRepository
{
   /** @var Explorer $database Database explorer. */
    private Explorer $database;
    private VineyardRepository $vineyardRepository;

    /**
     * Constructor of the TreatmentRepository.
     *
     * @param Explorer $database Database explorer.
     * @param VineyardRepository $vineyardRepository Vineyard repository.
     */
    public function __construct(Explorer $database, VineyardRepository $vineyardRepository)
    {
        $this->database = $database;
        $this->vineyardRepository = $vineyardRepository;
    }

    /**
     * fetchById
     * Method for fetch the joined treatment with task by id.
     * @param int Id of task/bottling wich will be fetch.
     * @return Treatment|null Object of treatment if this data exists, else return null.
     */
    public function fetchById(int $id): ?Treatment
    {
        $row = $this->database->table('TREATMENT')
            ->where('ID', $id)
            ->joinWhere('TASK', 'TASK.ID = ID_TASK')
            ->select('TREATMENT.*, TASK.*')
            ->fetch();

        if ($row === null) {
            return null;
        }

        /** @var Vineyard $vineyard */
        $vineyard = $this->vineyardRepository->fetchById((int)$row->ID_VINEYARD);
        if ($vineyard === null) {
            return null;
        }

        /** @var Treatment $treatment */
        $treatment = new Treatment(
            $row->ID,
            $row->NAME,
            TaskState::from((string)$row->STATE),
            $row->CREATION_TIMESTAMP,
            $row->LAST_CHANGE_TIMESTAMP,
            $row->PLANNED_EXECUTION_TIMESTAMP,
            $row->EXECUTION_TIMESTAMP,
            $row->NOTE,
            ($row->ID_TASK_ISSUER !== null) ? (int)$row->ID_TASK_ISSUER : 0,
            ($row->ID_TASK_CONFIRMER !== null) ? (int)$row->ID_TASK_CONFIRMER : 0,
            $vineyard,
            TreatmentType::from((string)$row->TYPE),
            $row->TREAT_NAME,
            $row->QUANTITY ? (float)$row->QUANTITY : null,
            $row->UNIT ? (string)$row->UNIT : null
        );

        return $treatment;
    }

    /**
     * fetchTreatments
     * Method to fetch treatments based on pattern and state.
     *
     * @param string $pattern Pattern to search for.
     * @param string $state State to filter by.
     * @return Treatment[] Array of Treatment objects.
     */
    public function fetchTreatments(string $pattern, string $state): array
    {

        // Fetch joined table of tasks and treatments
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

            /** @var Vineyard $vineyard */
            $vineyard = $this->vineyardRepository->fetchById((int) $row->ID_VINEYARD);
            if ($vineyard === null) {
                continue;
            }

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
                $vineyard,
                TreatmentType::from((string) $row->TYPE),
                (string) $row->TREAT_NAME,
                $row->QUANTITY ? (float) $row->QUANTITY : null,
                $row->UNIT ? (string) $row->UNIT : null
            );
        }

        return $treatments;
    }
}
