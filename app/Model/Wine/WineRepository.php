<?php
//FILE:     WineRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for handling Wine related database operations

namespace App\Model\Wine;

use App\Model\Image\ImageRepository;
use App\Model\Wine\Wine;
use App\Model\WineVariety\WineVariety;
use App\Model\Wine\WineState;
use App\Model\Bottle\BottleRepository;
use App\Model\Image\Image;
use App\Model\Task\Harvest\Harvest;
use App\Model\Task\TaskState;
use App\Model\WineVariety\WineVarietyType;
use Nette\Database\Explorer;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Handles wine related tasks and DB operations
 */
final class WineRepository
{
    /**
     * Repository constructor
     * 
     * Arguments provided by Nette DI container
     *
     * @param Explorer $db
     * @param ImageRepository $imageRepository
     * @param BottleRepository $btlRep
     */
    public function __construct(
        private Explorer $db,
        private ImageRepository $imageRepository,
        private BottleRepository $btlRep
    ) {}

    /**
     * Fetch a wine by its ID.
     *
     * @param int $wineId
     * @return Wine|null The Wine object if found, null otherwise.
     */
    public function fetchById(int $wineId): ?Wine
    {
        $wineRow = $this->db->table('WINE')->where('ID', $wineId)->fetch();
        return $wineRow ? $this->createWineFromRow($wineRow) : null;
    }

    /**
     * Fetch all wines from the database.
     *
     * @return Wine[] Array of Wine objects
     */
    public function fetchAll(): array
    {
        $rows = $this->db->table('WINE');
        $wines = [];

        foreach ($rows as $row) {
            $wines[] = $this->createWineFromRow($row);
        }

        return $wines;
    }
    
    /**
     * Fetch all wines that are in PRODUCTION state and not yet assigned to any bottling task.
     *
     * @return Wine[] Array of Wine objects
     */
    public function fetchAllProductionWines(): array
    {
        // exclude wines that already have a bottling record (i.e. already assigned to a bottling task)
        $bottledWineIds = $this->db->table('BOTTLING')->where("NOT STATE", TaskState::CANCELED->value)->select('ID_WINE');

        $rows = $this->db->table('WINE')
            ->where('STATE', WineState::PRODUCTION->value)
            ->where('ID NOT IN ?', $bottledWineIds);

        $wines = [];
        foreach ($rows as $row) {
            $wines[] = $this->createWineFromRow($row);
        }

        return $wines;
    }

    /**
     * Fetch all wines matching the given patterns.
     *
     * @param string $patternWines Search pattern for wine name, year, or variety
     * @param string $patternType Type filter
     * @param string $patternGrade Grade filter
     * @param string $patternState State filter
     * @return Wine[] Array of Wine objects
     */
    public function fetchAllWinesWithPattern(string $patternWines, string $patternType, string $patternGrade, string $patternState): array
    {
        $query = $this->db->table('WINE');

        // search through wine names, years, and wine varieties (through HARVEST table)
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

        // rest of filters (type, grade, state)
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

    /**
     * Fetch bottling info for a given wine and set it in the wine object.
     *
     * @param Wine $wine
     * @return bool True if bottling info was found and set, false otherwise.
     */
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
    
    /**
     * Create a Wine object from a database row.
     *
     * @param Nette\Database\Table\ActiveRow $row
     * @return Wine
     */
    public function createWineFromRow($row): Wine
    {
        // fetch associated image (via foreign key ID_PICTURE)
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
            alcoholPercentage: $row->ALCOHOL_PERCENTAGE,
            type: WineType::from((string) $row->TYPE),
            color: WineColor::from((string) $row->COLOR),
            grade: WineGrade::from((string) $row->GRADE),
            state: WineState::from((string) $row->STATE),
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

    /**
     * Update wine details in the database.
     *
     * @param Wine $wine
     * @return bool True on success, false on failure.
     */
    public function updateWine(Wine $wine): bool
    {
        try { // run within transaction
            return $this->db->transaction(function () use ($wine) {
                $this->db->table('WINE')
                    ->where('ID', $wine->id)
                    ->update([
                        'WINE_NAME' => $wine->getName(),
                        'YEAR' => $wine->getYear(),
                        'ALCOHOL_PERCENTAGE' => $wine->getAlcoholPercentage(),
                        'TYPE' => $wine->getType(),
                        'COLOR' => $wine->getColor(),
                        'GRADE' => $wine->getGrade(),
                        'VOLUME' => $wine->getVolume(),
                        'LEFT_SUGAR' => $wine->getLeftSugar(),
                        'ACID_CONTENTS' => $wine->getAcidContents(),
                        'PRODUCTION_NOTE' => $wine->getProductionNote(),
                    ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine: ' . $e->getMessage() . ' | Wine ID: ' . $wine->getId(), ILogger::ERROR);
            return false;
        }
    }

    /**
     * Set the sell approver for a given wine.
     *
     * @param int $wineId
     * @param int $userId
     * @return bool True on success, false on failure.
     */
    public function setSellApprover(int $wineId, int $userId): bool
    {
        try { // run within transaction
            return $this->db->transaction(function () use ($wineId, $userId) {
                $this->db->table('WINE')
                    ->where('ID', $wineId)
                    ->update([
                        'ID_SELL_APPROVER' => $userId,
                    ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to set sell approver: ' . $e->getMessage() . ' | Wine ID: ' . $wineId, ILogger::ERROR);
            return false;
        }
    }
    
    /**
     * Get total bottle count for a given wine.
     * 
     * @param int $idWine
     * @return int
     */
    public function getWineAllBottleCount(int $idWine) {
        $result = $this->btlRep->fetchByWineId($idWine);
        $count = 0;
        foreach($result as $oneItem) {
            $count += $oneItem->bottlesCount;
        }
        return $count;
    }
    
    /**
     * Update the number of free bottles for a given wine.
     * 
     * @param int $idWine
     * @param int $amount
     * @param bool $add True to add bottles, false to remove bottles
     * @return bool True on success, false on failure
     */
    public function updateWineFreeBottles(int $idWine, int $amount, bool $add): bool {

        $wine = $this->fetchById($idWine);        
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
        
        $newState = null;
        if ($newAmount == 0) {
           $newState = WineState::SOLD->value;
        } else {
            $newState = WineState::SALE->value;
        }
        
        $this->db->table("WINE")->where("ID", $idWine)->update([
            "BOTTLES_LEFT" => $newAmount,
            "STATE" => $newState
        ]);
        return true;
    }
    
    /**
     * Update the price of a given wine.
     * 
     * @param int $idWine
     * @param float $newValue
     * @param bool $current True to update current price, false to update bottle price
     * @return bool True on success, false on failure
     */
    public function updateWinePrice(int $idWine, float $newValue, bool $current): bool {
        
        $param = $current ? "CURRENT_PRICE" : "BOTTLE_PRICE";

        try { // run within transaction
            return $this->db->transaction(function () use ($idWine, $newValue, $param) {
                $this->db->table("WINE")
                ->where("ID", $idWine)
                ->update([
                    $param => $newValue
                ]);
                return true;
                
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine price: ' . $e->getMessage() . ' | Wine ID: ' . $idWine, ILogger::ERROR);
            return false;
        }
    }
    
    /**
     * Update the state of a given wine.
     * 
     * @param int $idWine
     * @param string $newState
     * @return bool True on success, false on failure
     */
    public function setWineState(int $idWine, string $newState): bool {

        try { // run within transaction
            return $this->db->transaction(function () use ($idWine, $newState) {
                $this->db->table("WINE")
                ->where("ID", $idWine)
                ->update([
                    "STATE" => $newState
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine state: ' . $e->getMessage() . ' | Wine ID: ' . $idWine, ILogger::ERROR);
            return false;
        }
    }
    
    /**
     * Update the state of a given wine.
     * 
     * @param int $idWine
     * @param string|null $newDescription
     * @return bool True on success, false on failure
     */
    public function setWineDescription(int $idWine, ?string $newDescription): bool {

        try { // run within transaction
            return $this->db->transaction(function () use ($idWine, $newDescription) {
                $this->db->table("WINE")
                ->where("ID", $idWine)
                ->update([
                    "SALES_DESCRIPTION" => $newDescription
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine description: ' . $e->getMessage() . ' | Wine ID: ' . $idWine, ILogger::ERROR);
            return false;
        }
    }

    /**
     * Produce wine from given harvests.
     *
     * @param Wine $wine
     * @param Harvest[] $harvests
     * @return boolean True on success, false on failure.
     */
    public function produceWine(Wine $wine, array $harvests): bool
    {
        try { // run within transaction
            return $this->db->transaction(function () use ($wine, $harvests) {
                // check that all harvests are correct and ready for PRODUCTION
                foreach ($harvests as $harvest) {
                    $harvestNew = $this->db->table('HARVEST')
                        ->where('ID_TASK', $harvest->getId())
                        ->joinWhere('TASK', 'TASK.ID = ID_TASK')
                        ->select('TASK.STATE, HARVEST.ID_HARVEST_CONFIRMER, HARVEST.ID_WINE')
                        ->fetch();
                    if ($harvestNew === null || $harvestNew->STATE !== TaskState::FINISHED->value || $harvestNew->ID_HARVEST_CONFIRMER === null || $harvestNew->ID_WINE !== null) {
                        throw new \Exception('One of the harvests is not ready for wine production.');
                    }
                }
                // insert new wine
                $result = $this->db->table('WINE')->insert([
                    'WINE_NAME' => $wine->getName(),
                    'YEAR' => $wine->getYear(),
                    'ALCOHOL_PERCENTAGE' => $wine->getAlcoholPercentage(),
                    'TYPE' => $wine->getType(),
                    'COLOR' => $wine->getColor(),
                    'GRADE' => $wine->getGrade(),
                    'STATE' => 'PRODUCTION',
                    'VOLUME' => $wine->getVolume(),
                    'LEFT_SUGAR' => $wine->getLeftSugar(),
                    'ACID_CONTENTS' => $wine->getAcidContents(),
                    'PRODUCTION_NOTE' => $wine->getProductionNote(),
                ]);

                if ($result === null) {
                    throw new \Exception('Failed to insert new wine into the database.');
                }
                // link harvests
                foreach ($harvests as $harvest) {
                    $this->db->table('HARVEST')
                        ->where('ID_TASK', $harvest->getId())
                        ->update([
                            'ID_WINE' => $result->ID,
                        ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to produce wine: ' . $e->getMessage() . ' | Wine: ' . $wine->getName(), ILogger::ERROR);
            return false;
        }
    }

    /**
     * Liquidate a wine.
     *
     * @param int $wineId
     * @param int $userId
     * @return boolean True on success, false on failure.
     */
    public function liquidateWine(int $wineId, int $userId): bool
    {
        try { // run within transaction
            return $this->db->transaction(function () use ($wineId, $userId) {
                // check wine exists and is in production state
                $wine = $this->db->table('WINE')
                    ->where('ID', $wineId)
                    ->fetch();
                
                if ($wine === null || $wine->STATE !== 'PRODUCTION') {
                    throw new \Exception('Wine not found or not in production state.');
                }

                // update wine state to liquidated
                $result = $this->db->table('WINE')
                    ->where('ID', $wineId)
                    ->update([
                        'STATE' => 'LIQUIDATED',
                        'ID_LIQUIDATOR' => $userId,
                    ]);
                if ($result === null) {
                    throw new \Exception('Failed to update wine state to liquidated.');
                }

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to liquidate wine ID ' . $wineId . ': ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }

    /**
     * Update the image associated with a wine.
     *
     * @param int $wineId
     * @param Image|null $image
     * @return bool True on success, false on failure.
     */
    public function updateWineImage(int $wineId, ?Image $image): bool
    {
        try { // run within transaction
            return $this->db->transaction(function () use ($wineId, $image) {
                $row = $this->db->table('WINE')->get($wineId);
                if ($row === null) {
                    throw new \Exception('Wine not found with ID ' . $wineId);
                }
               // handle picture update or insertion
                $pictureId = $row->ID_PICTURE;
                if ($image !== null) {
                    if ($pictureId !== null) {
                        // delete old image data
                        $this->imageRepository->deleteImageFromUploads($row->ref('PICTURE')->LINK);
                        // update existing picture
                        $this->db->table('PICTURE')->where('ID', $pictureId)->update([
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ]);
                    } else {
                        // insert new picture
                        $pictureData = [
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ];
                        $picture = $this->db->table('PICTURE')->insert($pictureData);
                        $pictureId = $picture->ID;
                    }
                } else {
                    // if no image was provided, remove existing picture reference
                    if ($pictureId !== null) {
                        $this->imageRepository->removeImage($pictureId);
                    }
                    $pictureId = null;
                }
                // update wine record
                $row->update([
                    'ID_PICTURE' => $pictureId
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine image for ID ' . $wineId . ': ' . $e->getMessage(), ILogger::ERROR);
            return false;
        }
    }
}
