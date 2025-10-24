<?php

declare(strict_types=1);


namespace App\Model\Task;

use App\Model\Task\Bottling\Bottling;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use App\Model\Task\TaskState;
use App\Model\Task\Treatment\TreatmentRepository;
use App\Model\Task\Bottling\BottlingRepository;
use App\Model\Task\Harvest\HarvestRepository;
use App\Model\Task\TaskData;
use App\Model\Image\Image as ImageEntity;
use App\Model\Validation\ValidationDefinitions;
use Tracy\Debugger;
use Tracy\ILogger;

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

    /**
     * fetchById
     * Method for fetch a task joined with its specific table (bottling, harvest, or treatment).
     *
     * @param int $id ID of the task.
     * @return Task|null Returns the corresponding Task object, or null if not found.
     */
    public function fetchById(int $id): ?Task
    {
       
        $bottling = $this->bottlingRepository->fetchById($id);
        if ($bottling !== null) {
            return $bottling;
        }
        $harvest = $this->harvestRepository->fetchById($id);
        if ($harvest !== null) {
            return $harvest;
        }
        $treatment = $this->treatmentRepository->fetchById($id);
        if ($treatment !== null) {
            return $treatment;
        }

        return null;

    }

    public function markTaskDone(Task $task, int $confirmerId, DateTime $executionDate): bool
    {
        try {
            return $this->database->transaction(function () use ($task, $confirmerId, $executionDate) {

                $this->database->table('TASK')
                    ->where('ID', $task->getId())
                    ->update([
                        'STATE' => TaskState::FINISHED->value,
                        'LAST_CHANGE_TIMESTAMP' => new DateTime(),
                        'EXECUTION_TIMESTAMP' => $executionDate,
                        'ID_TASK_CONFIRMER' => $confirmerId,
                    ]);

                // also update wine to bottled state if it was bottling
                if ($task instanceof Bottling) {
                    $this->database->table('WINE')
                        ->where('ID', $task->getWine()->getId())
                        ->update([
                            'STATE' => 'BOTTLED',
                        ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log("Failed to mark Task ID " . $task->getId() . " as done. Error: " . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }

    public function createBottlingTask(TaskData $data): bool {

        // Basic validation
        try {
            // name
            $name = trim($data->getName());
            if ($name === '' || strlen($name) < ValidationDefinitions::TASK_NAME_LENGTH_MIN) {
                throw new \Exception('Invalid task name');
            }

            // wine id
            $idWine = $data->getIdWine();
            if ($idWine === null) {
                throw new \Exception('No wine selected for bottling');
            }

            // bottle volume
            $bottleVolume = $data->getBottleVolume();
            if ($bottleVolume === null || !is_numeric($bottleVolume) || $bottleVolume <= 0.0) {
                throw new \Exception('Invalid bottle volume');
            }
            if ($bottleVolume < ValidationDefinitions::BOTTLE_VOLUME_MIN || $bottleVolume > ValidationDefinitions::BOTTLE_VOLUME_MAX) {
                throw new \Exception('Bottle volume out of allowed range');
            }

            // check wine exists and is in correct state (production)
            $wineRow = $this->database->table('WINE')->where('ID', $idWine)->fetch();
            if ($wineRow === null) {
                throw new \Exception('Specified wine does not exist');
            }

            // compute bottles count based on wine volume if possible
            $wineVolume = isset($wineRow->VOLUME) ? (float) $wineRow->VOLUME : 0.0;
            $bottlesCount = 0;
            if ($wineVolume > 0.0 && $bottleVolume > 0.0) {
                // assume VOLUME is in same units as bottleVolume (best-effort)
                $bottlesCount = (int) floor($wineVolume / ($bottleVolume*1000));
            }

            if ($bottlesCount <= 0) {
                throw new \Exception('Computed bottles count is zero or invalid');
            }

            // begin transaction
            $this->database->beginTransaction();

            // insert task row
            $now = new DateTime();
            $taskRow = $this->database->table('TASK')->insert([
                'NAME' => $name,
                'STATE' => TaskState::PLANNED->value,
                'CREATION_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'LAST_CHANGE_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'PLANNED_EXECUTION_TIMESTAMP' => $data->getPlannedTimestamp()?->format('Y-m-d H:i:s'),
                'EXECUTION_TIMESTAMP' => null,
                'NOTE' => $data->getNote(),
                'ID_TASK_ISSUER' => null,
                'ID_TASK_CONFIRMER' => null,
            ]);

            if ($taskRow === null || !isset($taskRow->ID)) {
                throw new \Exception('Failed to insert task');
            }

            $taskId = (int) $taskRow->ID;

            // handle optional image: insert into PICTURE and update wine picture
            $pictureId = null;
            $img = $data->getImg();
            if ($img instanceof ImageEntity) {
                $picture = $this->database->table('PICTURE')->insert([
                    'IMAGE_NAME' => $img->name,
                    'LINK' => $img->src,
                    'DESCRIPTION' => $img->description,
                ]);
                if ($picture === null || !isset($picture->ID)) {
                    throw new \Exception('Failed to insert picture');
                }
                $pictureId = (int) $picture->ID;

                // update wine picture reference
                $this->database->table('WINE')->where('ID', $idWine)->update([
                    'ID_PICTURE' => $pictureId,
                ]);
            }

            // insert bottling row
            $bottlingRow = $this->database->table('BOTTLING')->insert([
                'ID_TASK' => $taskId,
                'ID_WINE' => $idWine,
                'BOTTLES_COUNT' => $bottlesCount,
                'BOTTLE_VOLUME' => $bottleVolume,
            ]);

            if ($bottlingRow === null) {
                throw new \Exception('Failed to insert bottling');
            }

            // update wine state and bottles_left if appropriate
            $this->database->table('WINE')->where('ID', $idWine)->update([
                'BOTTLES_LEFT' => $bottlesCount,
            ]);

            // assign workers if provided
            $idsOfWorkers = $data->getIdsOfWorkers() ?? [];
            foreach ($idsOfWorkers as $workerId) {
                $this->database->table('WORKER')->insert([
                    'ID_USER' => $workerId,
                    'ID_TASK' => $taskId,
                ]);
            }

            $this->database->commit();
            return true;

        } catch (\Exception $e) {
            try {
                $this->database->rollBack();
            } catch (\Throwable $t) {
                // ignore
            }
            bdump('Failed to create bottling task: ' . $e->getMessage());
            return false;
        }

    }
}