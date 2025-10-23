<?php

declare(strict_types=1);


namespace App\Model\Task;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use App\Model\Task\TaskState;
use App\Model\Task\Treatment\TreatmentRepository;
use App\Model\Task\Bottling\BottlingRepository;
use App\Model\Task\Harvest\HarvestRepository;

class TaskRepository{
    private Explorer $database;
    private TreatmentRepository $treatmentRepository;
    private BottlingRepository $bottlingRepository;
    private HarvestRepository $harvestRepository;

    public function __construct(
        Explorer $database,
        TreatmentRepository $treatmentRepository,
        BottlingRepository $bottlingRepository,
        HarvestRepository $harvestRepository
    ) {
        $this->database = $database;
        $this->treatmentRepository = $treatmentRepository;
        $this->bottlingRepository = $bottlingRepository;
        $this->harvestRepository = $harvestRepository;
    }
    
    public function createNewTask(string $name, string $note, string $plannedDate): Task
    {
        // normalize planned date: treat empty string as null
        $planned = $plannedDate !== '' ? $plannedDate : null;
        $now = new DateTime();

        $row = $this->database->table('task')->insert([
            'name' => $name,
            'note' => $note,
            // store state as string from enum
            'state' => TaskState::PLANNED->value,
            // creation / last change timestamps
            'creation_timestamp' => $now->format('Y-m-d H:i:s'),
            'last_change_timestamp' => $now->format('Y-m-d H:i:s'),
            'planned_date' => $planned,
            'execution_timestamp' => null,
            'id_task_issuer' => null,
            'id_task_confirmer' => null,
        ]);

        return $this->createFromRow($row);
    }

    private function createFromRow($row): Task
    {
        // map state to enum, default to PLANNED when unknown/null
        $state = isset($row->state) ? (TaskState::tryFrom((string) $row->state) ?? TaskState::PLANNED) : TaskState::PLANNED;

        $creation = isset($row->creation_timestamp) ? new DateTime((string) $row->creation_timestamp) : new DateTime();
        $lastChange = isset($row->last_change_timestamp) ? new DateTime((string) $row->last_change_timestamp) : new DateTime();
        $planned = isset($row->planned_date) && $row->planned_date !== null ? new DateTime((string) $row->planned_date) : null;
        $execution = isset($row->execution_timestamp) && $row->execution_timestamp !== null ? new DateTime((string) $row->execution_timestamp) : null;

        $idTaskIssuer = isset($row->id_task_issuer) ? (int) $row->id_task_issuer : null;
        $idTaskConfirmer = isset($row->id_task_confirmer) ? (int) $row->id_task_confirmer : null;

        return new Task(
            (int) $row->id,
            (string) $row->name,
            $state,
            $creation,
            $lastChange,
            $planned,
            $execution,
            (string) ($row->note ?? ''),
            $idTaskIssuer,
            $idTaskConfirmer
        );
    }

    /**
     * fetchTasks
     * Method to fetch tasks.
     *
     * @param string $pattern Pattern to match task names.
     * @param string $type Task type to filter by.
     * @param string $state Task state to filter by.
     * @return array
     */
     public function fetchTasks(string $pattern, string $type, string $state): array
    {
        /** @var Task[] $tasks Array of all Tasks. */
        $tasks = [];

        // Filter by type
        if ($type !== '') {
            switch ($type) {
                case TaskType::HARVEST->value:
                    $tasks = $this->harvestRepository->fetchHarvests($pattern, $state);
                    break;
                case TaskType::TREATMENT->value:
                    $tasks = $this->treatmentRepository->fetchTreatments($pattern, $state);
                    break;
                case TaskType::BOTTLING->value:
                    $tasks = $this->bottlingRepository->fetchBottling($pattern, $state);
                    break;
            }
        } else { //No type is provided
            $harvests = $this->harvestRepository->fetchHarvests($pattern, $state);
            $treatments = $this->treatmentRepository->fetchTreatments($pattern, $state);
            $bottlings = $this->bottlingRepository->fetchBottling($pattern, $state);
            $tasks = array_merge($harvests, $treatments, $bottlings);
        }

        return $tasks;
    }

    
}