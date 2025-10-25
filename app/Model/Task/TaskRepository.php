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
                            'BOTTLES_LEFT' => $task->getBottlesCount(),
                        ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log("Failed to mark Task ID " . $task->getId() . " as done. Error: " . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }

    public function isUserAssignedToTask(int $userId, int $taskId): bool
    {
        $workerRow = $this->database->table('WORKER')
            ->where('ID_USER', $userId)
            ->where('ID_TASK', $taskId)
            ->fetch();

        return $workerRow !== null;
    }

    public function createBottlingTask(TaskData $data, int $userId): bool {

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

            // Validate users exist and have worker role
            $idsOfWorkers = $data->getIdsOfWorkers() ?? [];
            foreach ($idsOfWorkers as $workerId) {
                $userRow = $this->database->table('USER')
                    ->where('ID', $workerId)
                    ->where('ACTIVE', true)
                    ->fetch();
                
                if ($userRow === null) {
                    throw new \Exception('Invalid or inactive user: ' . $workerId);
                }
                
                if (!$userRow->ROLE_WORKER) {
                    throw new \Exception('User ' . $workerId . ' does not have worker role');
                }
            }

            // compute bottles count based on wine volume if possible
            $wineVolume = isset($wineRow->VOLUME) ? (float) $wineRow->VOLUME : 0.0;
            $bottlesCount = 0;
            if ($wineVolume > 0.0 && $bottleVolume > 0.0) {
                // assume VOLUME is in same units as bottleVolume (best-effort)
                $bottlesCount = (int) floor($wineVolume*1000 / ($bottleVolume));
            }

            if ($bottlesCount <= 0) {
                throw new \Exception('Computed bottles count is zero or invalid');
            }

            // begin transaction
            $this->database->beginTransaction();

            // insert task row
            $now = new DateTime();
            
            $timeStamp = $data->getPlannedTimestamp()?->format('Y-m-d H:i:s');

            $taskRow = $this->database->table('TASK')->insert([
                'NAME' => $name,
                'STATE' => $data->getIsDone() ? TaskState::FINISHED : TaskState::PLANNED->value,
                'CREATION_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'LAST_CHANGE_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'PLANNED_EXECUTION_TIMESTAMP' => $timeStamp, 
                'EXECUTION_TIMESTAMP' => $data->getIsDone() ? $timeStamp : null,
                'NOTE' => $data->getNote(),
                'ID_TASK_ISSUER' => $userId,
                'ID_TASK_CONFIRMER' => $data->getIsDone() ? $userId : null,
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
    
    public function createHarvestTask(TaskData $data, int $userId): bool {

        try {
            // validate basic fields
            $name = trim($data->getName());
            if ($name === '' || strlen($name) < ValidationDefinitions::TASK_NAME_LENGTH_MIN) {
                throw new \Exception('Invalid task name');
            }

            $vineyardId = $data->getIdWineYard();
            if ($vineyardId === null) {
                throw new \Exception('No vineyard selected');
            }

            $wineVarietyId = $data->getIdWineVariety();
            if ($wineVarietyId === null) {
                throw new \Exception('No wine variety selected');
            }

            $idRows = $data->getIdRows() ?? [];
            if (empty($idRows)) {
                throw new \Exception('No rows selected for harvest');
            }

            // validate each row: exists, belongs to vineyard and variety, is active
            foreach ($idRows as $code) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $vineyardId)
                    ->where('CODE', $code)
                    ->where('ID_WINE_VARIETY', $wineVarietyId)
                    ->where('ACTIVE', true)
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Invalid or inactive wine row selected: ' . $code);
                }
            }

            // Validate users exist and have worker role
            $idsOfWorkers = $data->getIdsOfWorkers() ?? [];
            foreach ($idsOfWorkers as $workerId) {
                $userRow = $this->database->table('USER')
                    ->where('ID', $workerId)
                    ->where('ACTIVE', true)
                    ->fetch();
                
                if ($userRow === null) {
                    throw new \Exception('Invalid or inactive user: ' . $workerId);
                }
                
                if (!$userRow->ROLE_WORKER) {
                    throw new \Exception('User ' . $workerId . ' does not have worker role');
                }
            }

            // start transaction
            $this->database->beginTransaction();
            
            $timeStamp = $data->getPlannedTimestamp()?->format('Y-m-d H:i:s');

            $now = new DateTime();
            $taskRow = $this->database->table('TASK')->insert([
                'NAME' => $name,
                'STATE' => $data->getIsDone() ? TaskState::FINISHED : TaskState::PLANNED->value,
                'CREATION_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'LAST_CHANGE_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'PLANNED_EXECUTION_TIMESTAMP' => $timeStamp, 
                'EXECUTION_TIMESTAMP' => $data->getIsDone() ? $timeStamp : null,
                'NOTE' => $data->getNote(),
                'ID_TASK_ISSUER' => $userId,
                'ID_TASK_CONFIRMER' => $data->getIsDone() ? $userId : null,
            ]);

            if ($taskRow === null || !isset($taskRow->ID)) {
                throw new \Exception('Failed to insert task');
            }

            $taskId = (int) $taskRow->ID;

            // insert harvest row
            $harvestRow = $this->database->table('HARVEST')->insert([
                'ID_TASK' => $taskId,
                'ID_VINEYARD' => $vineyardId,
                'ID_WINE_VARIETY' => $wineVarietyId,
                'ID_HARVEST_CONFIRMER' => null,
                'ID_WINE' => null,
                'WEIGHT' => null,
                'SUGAR_CONTENT' => null,
            ]);

            if ($harvestRow === null) {
                throw new \Exception('Failed to insert harvest');
            }

            // link selected rows in TASK_ROW
            foreach ($idRows as $code) {
                $this->database->table('TASK_ROW')->insert([
                    'ID_VINEYARD' => $vineyardId,
                    'CODE_ROW' => $code,
                    'ID_TASK' => $taskId,
                ]);
            }

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

    public function createTreatmentTask(TaskData $data, int $userId): bool {
        try {
            // Validate basic fields
            $name = trim($data->getName());
            if ($name === '' || strlen($name) < ValidationDefinitions::TASK_NAME_LENGTH_MIN) {
                throw new \Exception('Invalid task name');
            }

            $vineyardId = $data->getIdWineYard();
            if ($vineyardId === null) {
                throw new \Exception('No vineyard selected');
            }

            $idRows = $data->getIdRows() ?? [];
            if (empty($idRows)) {
                throw new \Exception('No rows selected for treatment');
            }

            // Validate treatment-specific fields
            $treatmentType = $data->getTreatmentType();
            if ($treatmentType === null) {
                throw new \Exception('No treatment type selected');
            }

            $treatmentName = trim($data->getTreatmentName());
            if ($treatmentName === '') {
                throw new \Exception('Treatment name is required');
            }

            $amount = $data->getAmount();
            if ($amount < ValidationDefinitions::TREATMENT_QUANTITY_MIN ||
                $amount > ValidationDefinitions::TREATMENT_QUANTITY_MAX) {
                throw new \Exception('Invalid amount');
            }

            $unit = trim($data->getUnit());
            if ($unit === '') {
                throw new \Exception('Unit is required');
            }

            // Validate each row: exists, belongs to vineyard, is active
            foreach ($idRows as $code) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $vineyardId)
                    ->where('CODE', $code)
                    ->where('ACTIVE', true)
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Invalid or inactive wine row selected: ' . $code);
                }
            }

            // Validate vineyard exists
            $vineyardRow = $this->database->table('VINEYARD')
                ->where('ID', $vineyardId)
                ->where('ACTIVE', true)
                ->fetch();
            if ($vineyardRow === null) {
                throw new \Exception('Invalid or inactive vineyard');
            }

            // Validate users exist and have worker role
            $idsOfWorkers = $data->getIdsOfWorkers() ?? [];
            foreach ($idsOfWorkers as $workerId) {
                $userRow = $this->database->table('USER')
                    ->where('ID', $workerId)
                    ->where('ACTIVE', true)
                    ->fetch();
                
                if ($userRow === null) {
                    throw new \Exception('Invalid or inactive user: ' . $workerId);
                }
                
                if (!$userRow->ROLE_WORKER) {
                    throw new \Exception('User ' . $workerId . ' does not have worker role');
                }
            }

            // Start transaction
            $this->database->beginTransaction();
            
            // Insert task row
            $now = new DateTime();
            $timeStamp = $data->getPlannedTimestamp()?->format('Y-m-d H:i:s');

            $taskRow = $this->database->table('TASK')->insert([
                'NAME' => $name,
                'STATE' => $data->getIsDone() ? TaskState::FINISHED : TaskState::PLANNED->value,
                'CREATION_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'LAST_CHANGE_TIMESTAMP' => $now->format('Y-m-d H:i:s'),
                'PLANNED_EXECUTION_TIMESTAMP' => $timeStamp, 
                'EXECUTION_TIMESTAMP' => $data->getIsDone() ? $timeStamp : null,
                'NOTE' => $data->getNote(),
                'ID_TASK_ISSUER' => $userId,
                'ID_TASK_CONFIRMER' => $data->getIsDone() ? $userId : null,
            ]);

            if ($taskRow === null || !isset($taskRow->ID)) {
                throw new \Exception('Failed to insert task');
            }

            $taskId = (int) $taskRow->ID;

            // Insert treatment row
            $treatmentRow = $this->database->table('TREATMENT')->insert([
                'ID_TASK' => $taskId,
                'ID_VINEYARD' => $vineyardId,
                'TYPE' => $treatmentType->value,
                'TREAT_NAME' => $treatmentName,
                'QUANTITY' => $amount,
                'UNIT' => $unit,
            ]);

            if ($treatmentRow === null) {
                throw new \Exception('Failed to insert treatment');
            }

            // Link selected rows in TASK_ROW
            foreach ($idRows as $code) {
                $this->database->table('TASK_ROW')->insert([
                    'ID_VINEYARD' => $vineyardId,
                    'CODE_ROW' => $code,
                    'ID_TASK' => $taskId,
                ]);
            }

            // Assign workers if provided
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
            Debugger::log('Failed to create treatment task: ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }
    
    public function updateBaseTask(int $taskId, \stdClass $values): bool {
        try {
            $taskRow = $this->database->table('TASK')->where('ID', $taskId)->fetch();
            if ($taskRow === null) {
                throw new \Exception('Task not found');
            }

            $update = [];
            // name
            if (property_exists($values, 'name')) {
                $name = trim((string) $values->name);
                if ($name === '' || strlen($name) < ValidationDefinitions::TASK_NAME_LENGTH_MIN) {
                    throw new \Exception('Invalid task name');
                }
                $update['NAME'] = $name;
            }

            // note
            if (property_exists($values, 'note')) {
                $update['NOTE'] = $values->note === null ? null : (string) $values->note;
            }

            // planned date (accept any DateTimeInterface instance or parse string)
            if (property_exists($values, 'planned_date') && $values->planned_date !== null) {
                if ($values->planned_date instanceof \DateTimeInterface) {
                    $update['PLANNED_EXECUTION_TIMESTAMP'] = $values->planned_date->format('Y-m-d H:i:s');
                } else {
                    // try to parse string
                    $dt = new \DateTime((string) $values->planned_date);
                    $update['PLANNED_EXECUTION_TIMESTAMP'] = $dt->format('Y-m-d H:i:s');
                }
            }

            // always update last change timestamp when something changed
            if (!empty($update)) {
                $update['LAST_CHANGE_TIMESTAMP'] = (new DateTime())->format('Y-m-d H:i:s');
                $this->database->table('TASK')->where('ID', $taskId)->update($update);
            }

            return true;
        } catch (\Exception $e) {
            Debugger::log('Failed to update base task ID ' . $taskId . ': ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }

    }
    
    public function updateBottlingTask(int $taskId, \stdClass $values, ?ImageEntity $img): bool {
        try {
            $this->database->beginTransaction();

            $taskRow = $this->database->table('TASK')->where('ID', $taskId)->fetch();
            if ($taskRow === null) {
                throw new \Exception('Task not found');
            }

            // fetch bottling row
            $bottlingRow = $this->database->table('BOTTLING')->where('ID_TASK', $taskId)->fetch();
            if ($bottlingRow === null) {
                throw new \Exception('Bottling record not found for task');
            }

            if (!empty($updatesTask)) {
                $updatesTask['LAST_CHANGE_TIMESTAMP'] = (new DateTime())->format('Y-m-d H:i:s');
                $this->database->table('TASK')->where('ID', $taskId)->update($updatesTask);
            }

            $wineId = $bottlingRow->ID_WINE;

            // bottle volume change
            $bottleVolume = $bottlingRow->BOTTLE_VOLUME;
            if (property_exists($values, 'volume') && $values->volume !== null) {
                $bv = (float) $values->volume;
                if ($bv < ValidationDefinitions::BOTTLE_VOLUME_MIN || $bv > ValidationDefinitions::BOTTLE_VOLUME_MAX) {
                    throw new \Exception('Bottle volume out of allowed range');
                }
                $bottleVolume = $bv;
            }

            // compute bottles count if wine volume available
            $wineRow = $this->database->table('WINE')->where('ID', $wineId)->fetch();
            $wineVolume = isset($wineRow->VOLUME) ? (float) $wineRow->VOLUME : 0.0;
            $bottlesCount = $bottlingRow->BOTTLES_COUNT;
            if ($wineVolume > 0.0 && $bottleVolume > 0.0) {
                $bottlesCount = (int) floor($wineVolume*1000 / ($bottleVolume));
            }

            // update bottling row
            $this->database->table('BOTTLING')->where('ID_TASK', $taskId)->update([
                'ID_WINE' => $wineId,
                'BOTTLES_COUNT' => $bottlesCount,
                'BOTTLE_VOLUME' => $bottleVolume,
            ]);

            // handle image: if provided, insert into PICTURE and update wine
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
                $this->database->table('WINE')->where('ID', $wineId)->update([
                    'ID_PICTURE' => $pictureId,
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
            Debugger::log('Failed to update bottling task ID ' . $taskId . ': ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }
    
    public function updateTreatmentTask(int $taskId, \stdClass $values): bool {
        try {
            $this->database->beginTransaction();

            $taskRow = $this->database->table('TASK')->where('ID', $taskId)->fetch();
            if ($taskRow === null) {
                throw new \Exception('Task not found');
            }

            $treatmentRow = $this->database->table('TREATMENT')->where('ID_TASK', $taskId)->fetch();
            if ($treatmentRow === null) {
                throw new \Exception('Treatment record not found for task');
            }

            if (!empty($updatesTask)) {
                $updatesTask['LAST_CHANGE_TIMESTAMP'] = (new DateTime())->format('Y-m-d H:i:s');
                $this->database->table('TASK')->where('ID', $taskId)->update($updatesTask);
            }

            // update treatment-specific fields
            $treatmentUpdates = [];
            if (property_exists($values, 'type')) {
                $treatmentUpdates['TYPE'] = (string) $values->type;
            }
            if (property_exists($values, 'treatmentName')) {
                $treatmentUpdates['TREAT_NAME'] = (string) $values->treatmentName;
            }
            if (property_exists($values, 'amount')) {
                $amt = (float) $values->amount;
                if ($amt < ValidationDefinitions::TREATMENT_QUANTITY_MIN || $amt > ValidationDefinitions::TREATMENT_QUANTITY_MAX) {
                    throw new \Exception('Invalid amount');
                }
                $treatmentUpdates['QUANTITY'] = $amt;
            }
            if (property_exists($values, 'unit')) {
                $treatmentUpdates['UNIT'] = (string) $values->unit;
            }

            if (!empty($treatmentUpdates)) {
                $this->database->table('TREATMENT')->where('ID_TASK', $taskId)->update($treatmentUpdates);
            }

            $this->database->commit();
            return true;
        } catch (\Exception $e) {
            try {
                $this->database->rollBack();
            } catch (\Throwable $t) {
                // ignore
            }
            Debugger::log('Failed to update treatment task ID ' . $taskId . ': ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }

    }

}
