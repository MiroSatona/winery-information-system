<?php

namespace App\Model\Wine;

use App\Model\Image\ImageRepository;
use App\Model\Wine\Wine;
use App\Model\WineVariety\WineVariety;
use App\Model\Bottle\BottleRepository;
use App\Model\Image\Image;
use App\Model\Task\Harvest\Harvest;
use App\Model\Task\TaskState;
use App\Model\WineVariety\WineVarietyType;
use App\Model\Vineyard\Vineyard;
use App\Model\Vineyard\VineyardRepository;
use App\Model\WineVariety\WineVarietyRepository;
use Nette\Database\Explorer;
use Tracy\Debugger;

final class WineRepository
{
    public function __construct(
        private Explorer $db,
        private ImageRepository $imageRepository,
        private BottleRepository $btlRep,
        private VineyardRepository $vineyardRepository,
        private WineVarietyRepository $wineVarietyRepository
    ) {}

    public function fetchWineById(int $wineId): ?Wine
    {
        $wineRow = $this->db->table('WINE')->where('ID', $wineId)->fetch();
        return $wineRow ? $this->createWineFromRow($wineRow) : null;
    }

    /**
     * Fetch all wines from the database.
     *
     * @return Wine[] Array of Wine objects
     */
    public function fetchAllWines(): array
    {
        $rows = $this->db->table('WINE');
        $wines = [];

        foreach ($rows as $row) {
            $wines[] = $this->createWineFromRow($row);
        }

        return $wines;
    }

    /**
     * Undocumented function
     *
     * @return Harvest[]
     */
    public function fetchAllHarvestsReadyForProduction(): array
    {
        // harvest needs to have state FINISHED, cannot be yet used and it must have WINEGROWER id set (not null)
        $result = $this->db->table('HARVEST')
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

    public function fetchAllWinesWithPattern(string $patternWines, string $patternType, string $patternGrade, string $patternState): array
    {
        $query = $this->db->table('WINE');

        // Search through wine names, years, and wine varieties (through HARVEST table)
        if ($patternWines !== '') {
            $like = '%' . $patternWines . '%';

            $varietyIds = $this->db->table('WINE_VARIETY')
                ->where('NAME LIKE ?', $like)
                ->select('ID');
            
            $wineIdsFromVarieties = $this->db->table('HARVEST')
                ->where('ID_WINE_VARIETY IN ?', $varietyIds)
                ->select('DISTINCT ID_WINE');
            
            $query = $query->where(
                'WINE_NAME LIKE ? OR YEAR LIKE ? OR ID IN ?', 
                $like, 
                $like, 
                $wineIdsFromVarieties
            );
        }

        // Rest of filters (type, grade, state)
        if ($patternType !== 'all') {
            $query = $query->where('TYPE', $patternType);
        }

        if ($patternGrade !== 'all') {
            $query = $query->where('GRADE', $patternGrade);
        }

        if ($patternState !== 'all') {
            $query = $query->where('STATE', $patternState);
        }

        $rows = $query->fetchAll();
        $wines = [];

        foreach ($rows as $row) {
            $wines[] = $this->createWineFromRow($row);
        }

        return $wines;
    }

    public function fetchBottlingInfoForWine(Wine $wine): bool
    {
        $bottles = $this->db->table("BOTTLING")
            ->where("ID_WINE", $wine->id)->fetch();

        if ($bottles == null) {
            return false;
        }

        $wine->setBottlesTotal((int) $bottles->BOTTLES_COUNT);
        $wine->setBottleVolume($bottles->BOTTLE_VOLUME);
        return true;
    }
    
    public function createWineFromRow($row): Wine
    {
        // Fetch associated image (via foreign key ID_PICTURE)
        $image = null;
        if ($row->ID_PICTURE !== null) {
            $image = $this->imageRepository->fetchImageById((int) $row->ID_PICTURE);
        }
        
        $varieties = $this->db->table("HARVEST")
            ->where("ID_WINE", $row->ID)
            ->select("DISTINCT WINE_VARIETY.NAME, WINE_VARIETY.ID_PICTURE AS VARIETY_ID_PICTURE, WINE_VARIETY.COLOR, WINE_VARIETY.DESCRIPTION, WINE_VARIETY.ID")
            ->joinWhere("WINE_VARIETY", "WINE_VARIETY.ID = HARVEST.ID_WINE_VARIETY")
            ->fetchAll();

        /** @var WineVariety[] $varietiesData */
        $varietiesData = [];
        foreach ($varieties as $varietyRow) {
            /** @var Image $image */
            $imageVariety = null;
            if ($varietyRow->VARIETY_ID_PICTURE !== null) {
                $imageVariety = $this->imageRepository->fetchImageById((int) $varietyRow->VARIETY_ID_PICTURE);
            }
            $varietiesData[] = new WineVariety(
                id: (int) $varietyRow->ID,
                name: (string) $varietyRow->NAME,
                color: WineVarietyType::from((string) $varietyRow->COLOR),
                image: $imageVariety,
                description: (string) $varietyRow->DESCRIPTION
            );
        }

        $varietyNames = array_map(fn($v) => $v->NAME, $varieties);
        
        Debugger::log("varieties: " . implode(", ", $varietyNames));

        return new Wine(
            id: (int) $row->ID,
            idLiquidator: $row->ID_LIQUIDATOR !== null ? (int) $row->ID_LIQUIDATOR : null,
            idSellApprover: $row->ID_SELL_APPROVER !== null ? (int) $row->ID_SELL_APPROVER : null,
            year: (int) $row->YEAR,
            alcoholPercentage: (int) $row->ALCOHOL_PERCENTAGE,
            type: (string) $row->TYPE,
            color: (string) $row->COLOR,
            grade: (string) $row->GRADE,
            state: (string) $row->STATE,
            volume: (int) $row->VOLUME,
            bottlePrice: $row->BOTTLE_PRICE !== null ? (int) $row->BOTTLE_PRICE : null,
            currentPrice: $row->CURRENT_PRICE !== null ? (int) $row->CURRENT_PRICE : null,
            bottlesLeft: $row->BOTTLES_LEFT !== null ? (int) $row->BOTTLES_LEFT : null,
            leftSugar: $row->LEFT_SUGAR !== null ? (int) $row->LEFT_SUGAR : null,
            acidContents: $row->ACID_CONTENTS !== null ? (int) $row->ACID_CONTENTS : null,
            salesDescription: $row->SALES_DESCRIPTION !== null ? (string) $row->SALES_DESCRIPTION : null,
            productionNote: (string) $row->PRODUCTION_NOTE,
            name: (string) $row->WINE_NAME,
            img: $image,
            variety: $varietyNames,
            varieties: $varietiesData
        );
    }
    
    public function getWineAllBottleCount(int $idWine) {
        $result = $this->btlRep->fetchByWineId($idWine);
        $count = 0;
        foreach($result as $oneItem) {
            $count += $oneItem->bottlesCount;
        }
        return $count;
    }
    
    public function updateWineFreeBottles(int $idWine, int $amount, bool $add): bool {

        $wine = $this->fetchWineById($idWine);        
        if ($wine == null) {
            Debugger::log('Failed to find specified wine');
            return false;
        }
        
        $currentCount = $wine->bottlesLeft;
        
        if ($add) {
            $newAmount = $currentCount + $amount;
        } else {
            $newAmount = $currentCount - $amount;
        }
        
        if ($newAmount < 0) {
            Debugger::log('the new amount < 0');
            return false;
        }
       
        $maxVal = $this->getWineAllBottleCount($idWine);
            
        if ($newAmount > $maxVal) {
            Debugger::log("the new amount is: $newAmount");
            Debugger::log("the max amount is : $maxVal");
            Debugger::log('the amount is > than max');
            return false;
        }
        
        $this->db->table("WINE")->where("ID", $idWine)->update([
           "BOTTLES_LEFT" => $newAmount,
        ]);
        return true;
    }
    
    public function updateWinePrice(int $idWine, float $newValue, bool $current): bool {
        
        $param = $current ? "CURRENT_PRICE" : "BOTTLE_PRICE";
        $this->db->beginTransaction();
        if (!$this->fetchWineById($idWine)) {
            return false;
        }
        try {
            $this->db->table("WINE")
            ->where("ID", $idWine)
            ->update([
                $param => $newValue
            ]);
            $this->db->commit();
        } catch(\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        return true;
    }
    
    public function setWineState(int $idWine, string $newState): bool {

        $wine = $this->fetchWineById($idWine);
        if (!$wine) {
            return false;
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->table("WINE")
            ->where("ID", $idWine)
            ->update([
                "STATE" => $newState
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function setWineDescription(int $idWine, string $newDescription): bool {

        $wine = $this->fetchWineById($idWine);
        if (!$wine) {
            return false;
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->table("WINE")
            ->where("ID", $idWine)
            ->update([
               "SALES_DESCRIPTION" => $newDescription
            ]);
            $this->db->commit();
            return true; 
        } catch(\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
