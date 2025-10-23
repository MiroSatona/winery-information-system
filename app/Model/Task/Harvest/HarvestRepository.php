<?php

declare(strict_types=1);

namespace App\Model\Task\Harvest;


use App\Model\Task\Harvest\Harvest;
use App\Model\Vineyard\VineyardRepository;
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\Task\TaskState;
use Nette\Database\Explorer;
use Tracy\Debugger;
use Tracy\ILogger;

final class HarvestRepository
{
    public function __construct(
        private Explorer $database,
        private VineyardRepository $vineyardRepository,
        private WineVarietyRepository $wineVarietyRepository
    ) {
    }

    public function fetchById(int $id): ?Harvest
    {
        $row = $this->database->table('HARVEST')
            ->where('ID', $id)
            ->joinWhere('TASK', 'TASK.ID = ID_TASK')
            ->select('HARVEST.*, TASK.*')
            ->fetch();
        
        if ($row === null) {
            return null;
        }

        $vineyard = $this->vineyardRepository->fetchById((int)$row->ID_VINEYARD);
        $wineVariety = $this->wineVarietyRepository->fetchById((int)$row->ID_WINE_VARIETY);
        if ($vineyard === null || $wineVariety === null) {
            Debugger::log("Failed to fetch associated Vineyard or WineVariety for Harvest ID " . $row->ID);
            return null;
        }

        /** @var Harvest $harvest */
        $harvest = new Harvest(
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
            $wineVariety,
            ($row->ID_HARVEST_CONFIRMER !== null) ? (int)$row->ID_HARVEST_CONFIRMER : null,
            ($row->ID_WINE !== null) ? (int)$row->ID_WINE : null,
            ($row->WEIGHT !== null) ? (float)$row->WEIGHT : null,
            ($row->SUGAR_CONTENT !== null) ? (float)$row->SUGAR_CONTENT : null
        );

        return $harvest;
    }

    /**
     * Undocumented function
     *
     * @return Harvest[]
     */
    public function fetchAllHarvestsReadyForProduction(): array
    {
        // harvest needs to have state FINISHED, cannot be yet used and it must have WINEGROWER id set (not null)
        $result = $this->database->table('HARVEST')
            ->joinWhere('TASK', 'TASK.ID = ID')
            ->where('STATE', 'FINISHED')
            ->where('ID_WINE IS NULL')
            ->where('ID_HARVEST_CONFIRMER IS NOT NULL')
            ->select('HARVEST.*, TASK.*')
            ->fetchAll();

        /**$result = $this->db->table('TASK')
            ->where('STATE', 'FINISHED')
            ->joinWhere('HARVEST', 'HARVEST.ID = TASK.ID')
            ->where('HARVEST.ID_WINE IS NULL')
            ->where('HARVEST.ID_WINEGROWER IS NOT NULL')
            ->fetchAll();**/
        
        /** @var Harvest[] $harvests */
        $harvests = [];
        foreach ($result as $row) {
            $vineyard = $this->vineyardRepository->fetchById((int)$row->ID_VINEYARD);
            $wineVariety = $this->wineVarietyRepository->fetchById((int)$row->ID_WINE_VARIETY);
            if ($vineyard === null || $wineVariety === null) {
                Debugger::log("Failed to fetch associated Vineyard or WineVariety for Harvest ID " . $row->ID);
                continue;
            }

            $harvests[] = new Harvest(
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
                $wineVariety,
                ($row->ID_HARVEST_CONFIRMER !== null) ? (int)$row->ID_HARVEST_CONFIRMER : null,
                ($row->ID_WINE !== null) ? (int)$row->ID_WINE : null,
                ($row->WEIGHT !== null) ? (float)$row->WEIGHT : null,
                ($row->SUGAR_CONTENT !== null) ? (float)$row->SUGAR_CONTENT : null
            );
        }
    
        return $harvests;
    }

    /**
     * fetchHarvests
     * Method to fetch harvests based on pattern and state.
     *
     * @param string $pattern Pattern to search for.
     * @param string $state State to filter by.
     * @return Harvest[] Array of Harvest objects.
     */
    public function fetchHarvests(string $pattern, string $state): array
    {
        // Fetch joined teble of tasks and harvests
        $mainQuery = $this->database->table('HARVEST')
                        ->joinWhere('TASK', 'TASK.ID = ID_TASK')
                        ->select('TASK.*, HARVEST.*');

        // Filter by pattern and state
        if ($pattern !== '') {
            $mainQuery->where('name LIKE ?', "%$pattern%");
        }

        if ($state !== '') {
            $mainQuery->where('state = ?', $state);
        }
        
        // Fetch all filtered data
        $mainQuery->fetchAll();

        /** @var Harvest[] */
        $harvests = [];
        
        foreach ($mainQuery as $row) {
            $vineyard = $this->vineyardRepository->fetchById((int)$row->ID_VINEYARD);
            $wineVariety = $this->wineVarietyRepository->fetchById((int)$row->ID_WINE_VARIETY);
            if ($vineyard === null || $wineVariety === null) {
                continue;
            }

            $harvests[] = new Harvest(
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
                $wineVariety,
                ($row->ID_HARVEST_CONFIRMER !== null) ? (int)$row->ID_HARVEST_CONFIRMER : null,
                ($row->ID_WINE !== null) ? (int)$row->ID_WINE : null,
                ($row->WEIGHT !== null) ? (float)$row->WEIGHT : null,
                ($row->SUGAR_CONTENT !== null) ? (float)$row->SUGAR_CONTENT : null
            );
        }

        return $harvests;
    }
}   