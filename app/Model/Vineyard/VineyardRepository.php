<?php

declare(strict_types=1);

namespace App\Model\Vineyard;

use App\Model\Image\Image;
use App\Model\WineRow\WineRow;
use App\Model\WineVariety\WineVariety;
use App\Model\WineVariety\WineVarietyRepository;
use Tracy\Debugger;
use Tracy\ILogger;
use Nette\Database\Explorer;


final class VineyardRepository
{
    public function __construct(
        private Explorer $database,
        private WineVarietyRepository $wineVarietyRepository
    ) {
    }

    /**
     * @return Vineyard[]
     */
    public function fetchAll(): array
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = [];
        $rows = $this->database->table('VINEYARD')->fetchAll();

        foreach ($rows as $row) {
            // Access PICTURE data by left join
            $picture = $row->ref('PICTURE', 'ID_PICTURE');
            $image = null;
            if ($picture) {
                $image = new Image(
                    $picture->ID,
                    $picture->LINK,
                    $picture->IMAGE_NAME,
                    $picture->DESCRIPTION
                );
            }

            $vineyards[] = new Vineyard(
                $row->ID,
                (bool)$row->ACTIVE,
                $row->NAME,
                $row->LOCATION,
                $image,
                $row->DESCRIPTION,
                $row->YEAR
            );
        }
        return $vineyards;
    }

    public function fetchById(int $id): ?Vineyard
    {
        $row = $this->database->table('VINEYARD')->get($id);
        if ($row === null) {
            return null;
        }

        // Access PICTURE data by left join
        $picture = $row->ref('PICTURE', 'ID_PICTURE');
        $image = null;
        if ($picture) {
            $image = new Image(
                $picture->ID,
                $picture->LINK,
                $picture->IMAGE_NAME,
                $picture->DESCRIPTION
            );
        }

        return new Vineyard(
            $row->ID,
            (bool)$row->ACTIVE,
            $row->NAME,
            $row->LOCATION,
            $image,
            $row->DESCRIPTION,
            $row->YEAR
        );
    }

    /**
     * @return Vineyard[]
     */
    public function fetchAllWithPattern(string $pattern, string $type): array
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = [];
        $rowsSelection = $this->database->table('VINEYARD')
            ->where('NAME LIKE ? OR LOCATION LIKE ?', "%$pattern%", "%$pattern%");

        if ($type === 'active') {
            $rowsSelection = $rowsSelection->where('ACTIVE', true);
        } elseif ($type === 'inactive') {
            $rowsSelection = $rowsSelection->where('ACTIVE', false);
        }

        $rows = $rowsSelection->fetchAll();

        foreach ($rows as $row) {
            // Access PICTURE data by left join
            $picture = $row->ref('PICTURE', 'ID_PICTURE');
            $image = null;
            if ($picture) {
                $image = new Image(
                    $picture->ID,
                    $picture->LINK,
                    $picture->IMAGE_NAME,
                    $picture->DESCRIPTION
                );
            }

            $vineyards[] = new Vineyard(
                $row->ID,
                (bool)$row->ACTIVE,
                $row->NAME,
                $row->LOCATION,
                $image,
                $row->DESCRIPTION,
                $row->YEAR
            );
        }
        return $vineyards;
    }

    public function getVineyardById(int $id): ?Vineyard
    {
        $row = $this->database->table('VINEYARD')->get($id);
        if ($row === null) {
            return null;
        }

        // Access PICTURE data by left join
        $picture = $row->ref('PICTURE', 'ID_PICTURE');
        $image = null;
        if ($picture) {
            $image = new Image(
                $picture->ID,
                $picture->LINK,
                $picture->IMAGE_NAME,
                $picture->DESCRIPTION
            );
        }

        return new Vineyard(
            $row->ID,
            (bool)$row->ACTIVE,
            $row->NAME,
            $row->LOCATION,
            $image,
            $row->DESCRIPTION,
            $row->YEAR
        );
    }


    public function addVineyard(Vineyard $vineyard): bool
    {
        try {
            return $this->database->transaction(function () use ($vineyard) {
                // Handle picture insertion
                $pictureId = null;
                $image = $vineyard->getImage();
                if ($image !== null) {
                    $pictureData = [
                        'LINK' => $image->src,
                        'IMAGE_NAME' => $image->name,
                        'DESCRIPTION' => $image->description
                    ];
                    $picture = $this->database->table('PICTURE')->insert($pictureData);
                    $pictureId = $picture->ID;
                }
                $data = [
                    'NAME' => $vineyard->getName(),
                    'LOCATION' => $vineyard->getLocation(),
                    'ID_PICTURE' => $pictureId,
                    'DESCRIPTION' => $vineyard->getDescription(),
                    'YEAR' => $vineyard->getYearEstablished()
                ];
                $this->database->table('VINEYARD')->insert($data);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to add vineyard: ' . $e->getMessage() . ' | Vineyard: ' . $vineyard->getName(), ILogger::ERROR);
            return false;
        }
    }

    public function updateVineyard(Vineyard $vineyard): bool
    {
        try {
            return $this->database->transaction(function () use ($vineyard) {
                $row = $this->database->table('VINEYARD')->get($vineyard->getId());
                if ($row === null) {
                    throw new \Exception('Vineyard not found with ID ' . $vineyard->getId());
                }
                // Handle picture update or insertion
                $pictureId = $row->ID_PICTURE;
                $image = $vineyard->getImage();
                if ($image !== null) {
                    if ($pictureId !== null) {
                        // Update existing picture
                        $this->database->table('PICTURE')->where('ID', $pictureId)->update([
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ]);
                    } else {
                        // Insert new picture
                        $pictureData = [
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ];
                        $picture = $this->database->table('PICTURE')->insert($pictureData);
                        $pictureId = $picture->ID;
                    }
                } else {
                    // If no image provided, remove existing picture reference
                    if ($pictureId !== null) {
                        $this->database->table('PICTURE')->where('ID', $pictureId)->delete();
                        $pictureId = null;
                    }
                }
                // Update vineyard record
                $row->update([
                    'NAME' => $vineyard->getName(),
                    'LOCATION' => $vineyard->getLocation(),
                    'ID_PICTURE' => $pictureId,
                    'DESCRIPTION' => $vineyard->getDescription(),
                    'YEAR' => $vineyard->getYearEstablished()
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $vineyard->getId(), ILogger::ERROR);
            return false;
        }
    }

    public function deactivateVineyard(int $id): bool
    {
        try {
            return $this->database->transaction(function () use ($id) {
                $row = $this->database->table('VINEYARD')->get($id);
                if ($row === null) {
                    throw new \Exception('Vineyard not found with ID ' . $id);
                }
                $row->update(['ACTIVE' => false]);

                // also set all associated wine rows to inactive
                $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $id)
                    ->update(['ACTIVE' => false]);

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to deactivate vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }

    public function reactivateVineyard(int $id): bool
    {
        try {
            return $this->database->transaction(function () use ($id) {
                $row = $this->database->table('VINEYARD')->get($id);
                if ($row === null) {
                    throw new \Exception('Vineyard not found with ID ' . $id);
                }
                $row->update(['ACTIVE' => true]);

                // also set all associated wine rows to active
                $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $id)
                    ->update(['ACTIVE' => true]);
                
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to activate vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }

    /**
     * @return array
     */
    public function joinWithVarieties(): array{

        /** @var Vineyard $vineyards Array of all vineyards in database*/
        $vineyards = $this->fetchAll();
        $joinedData = [];

        foreach ($vineyards as $yard) {
            /** @var Row[] $wineRows Fetched rows of current vine yard <-> yard */
            $wineRows = $this->database->table('WINE_ROW')
                ->where('ID_VINEYARD', $yard->getId())
                ->fetchAll();

            /** @var Row[] $varietyNames Fetched rows of current vine yard <-> yard */
            $varietyNames = [];

            foreach ($wineRows as $row) {
                /** @var Row $variety Fetched variety of the current wine row <-> row */
                $variety = $this->database->table('WINE_VARIETY')->where('ID', $row->ID_WINE_VARIETY)->fetch();
                if ($variety && !in_array($variety->NAME, $varietyNames, true)) {
                    $varietyNames[] = $variety->NAME;
                }
            }

            /** @var array $joinedData Array of vineyards with theirs varieties. */
            $joinedData[] = [
                'vineyard' => $yard,
                'varieties' => $varietyNames
            ];
        }
        return $joinedData;
    }

    public function getWineRowsOnVineyard(int $vineyardId): array
    {
        /** @var WineRow[] $wineRows Array of wine rows on the given vineyard */
        $wineRows = [];
        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->fetchAll();

        foreach ($rows as $row) {
            $wineRows[] = new WineRow(
                $row->CODE,
                (bool)$row->ACTIVE,
                $row->ID_VINEYARD,
                $row->ID_WINE_VARIETY,
                $row->HEAD_COUNT,
                $row->PLANTING_YEAR
            );
        }

        return $wineRows;
    }

    public function getWineRowsOnVineyardWithPattern(int $vineyardId, string $pattern, string $type): array
    {
        /** @var WineRow[] $wineRows Array of wine rows on the given vineyard */
        $wineRows = [];
        // fetch wine rows matching the pattern in CODE, PLANTING_YEAR, or associated WINE_VARIETY name
        $rowsSelection = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('CODE LIKE ? OR PLANTING_YEAR LIKE ? OR ID_WINE_VARIETY IN ?', "%$pattern%", "%$pattern%", $this->database->table('WINE_VARIETY')->where('NAME LIKE ?', "%$pattern%")->select('ID'));
        
        if ($type === 'active') {
            $rowsSelection = $rowsSelection->where('ACTIVE', true);
        } elseif ($type === 'inactive') {
            $rowsSelection = $rowsSelection->where('ACTIVE', false);
        }

        $rows = $rowsSelection->fetchAll();

        foreach ($rows as $row) {
            $wineRows[] = new WineRow(
                $row->CODE,
                (bool)$row->ACTIVE,
                $row->ID_VINEYARD,
                $row->ID_WINE_VARIETY,
                $row->HEAD_COUNT,
                $row->PLANTING_YEAR
            );
        }

        return $wineRows;
    }

    public function getWineRowById(int $vineyardId, int $wineRowCode): ?WineRow
    {
        $row = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('CODE', $wineRowCode)
            ->fetch();

        if ($row === null) {
            return null;
        }

        return new WineRow(
            $row->CODE,
            (bool)$row->ACTIVE,
            $row->ID_VINEYARD,
            $row->ID_WINE_VARIETY,
            $row->HEAD_COUNT,
            $row->PLANTING_YEAR
        );
    }

    public function getUniqueWineVarietiesOnVineyard(int $vineyardId): array
    {
        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = [];
        
        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->select('DISTINCT ID_WINE_VARIETY')
            ->fetchAll();

        foreach ($rows as $row) {
            $variety = $this->wineVarietyRepository->fetchById($row->ID_WINE_VARIETY);
            if ($variety) {
                $wineVarieties[] = $variety;
            }
        }

        return $wineVarieties;
    }

    public function getWineVarietiesCountOnVineyard(int $vineyardId): int
    {
       return $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->select('DISTINCT ID_WINE_VARIETY')
            ->count();
    }

    public function deactivateWineRow(int $vineyardId, int $wineRowCode): bool
    {
        try {
            return $this->database->transaction(function () use ($vineyardId, $wineRowCode) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $vineyardId)
                    ->where('CODE', $wineRowCode)
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Wine row not found on vineyard: ' . $vineyardId . ' with code: ' . $wineRowCode);
                }
                $row->update(['ACTIVE' => false]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to deactivate wine row from vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $vineyardId . ' | Wine Row Code: ' . $wineRowCode, ILogger::ERROR);
            return false;
        }
    }

    public function reactivateWineRow(int $vineyardId, int $wineRowCode): bool
    {
        try {
            return $this->database->transaction(function () use ($vineyardId, $wineRowCode) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $vineyardId)
                    ->where('CODE', $wineRowCode)
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Wine row not found on vineyard: ' . $vineyardId . ' with code: ' . $wineRowCode);
                }
                $row->update(['ACTIVE' => true]);

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to reactivate wine row on vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $vineyardId . ' | Wine Row Code: ' . $wineRowCode, ILogger::ERROR);
            return false;
        }
    }

    public function getWineVarietyForWineRow(int $vineyardId, int $wineRowCode): ?WineVariety
    {
        // For composite primary keys, use where() with both key parts
        $row = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('CODE', $wineRowCode)
            ->fetch();
            
        if ($row === null) {
            return null;
        }

        return $this->wineVarietyRepository->fetchById($row->ID_WINE_VARIETY);
    }

    public function addWineRowToVineyard(WineRow $wineRow): bool
    {
        try {
            return $this->database->transaction(function () use ($wineRow) {
                $data = [
                    'CODE' => $wineRow->getId(),
                    'ID_VINEYARD' => $wineRow->getVineyardId(),
                    'ID_WINE_VARIETY' => $wineRow->getWineVarietyId(),
                    'HEAD_COUNT' => $wineRow->getHeadCount(),
                    'PLANTING_YEAR' => $wineRow->getYearPlanted()
                ];
                $this->database->table('WINE_ROW')->insert($data);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to add wine row to vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $wineRow->getVineyardId(), ILogger::ERROR);
            return false;
        }
    }

    public function updateWineRowOnVineyard(WineRow $wineRow): bool
    {
        try {
            return $this->database->transaction(function () use ($wineRow) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $wineRow->getVineyardId())
                    ->where('CODE', $wineRow->getId())
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Wine row not found on vineyard: ' . $wineRow->getVineyardId() . ' with code: ' . $wineRow->getId());
                }
                $row->update([
                    'CODE' => $wineRow->getId(),
                    'ID_WINE_VARIETY' => $wineRow->getWineVarietyId(),
                    'HEAD_COUNT' => $wineRow->getHeadCount(),
                    'PLANTING_YEAR' => $wineRow->getYearPlanted()
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to edit wine row on vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $wineRow->getVineyardId() . ' | Wine Row Code: ' . $wineRow->getId(), ILogger::ERROR);
            return false;
        }
    }

    public function getWinLeVarietyTotalHeadCountOnVineyard(int $vineyardId, int $wineVarietyId): int
    {
        $totalHeadCount = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('ID_WINE_VARIETY', $wineVarietyId)
            ->sum('HEAD_COUNT');

        return (int)$totalHeadCount;
    }
    
    public function getRowsAtVineyardWithWineVariety(int $vineyardId, int $wineVarietyId) {
        /** @var WineRow[] $wineRows Array of wine rows matching given variety on the given vineyard */
        $wineRows = [];
        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('ID_WINE_VARIETY', $wineVarietyId)
            ->where('ACTIVE', true)
            ->fetchAll();

        foreach ($rows as $row) {
            $wineRows[] = new WineRow(
                $row->CODE,
                (bool)$row->ACTIVE,
                $row->ID_VINEYARD,
                $row->ID_WINE_VARIETY,
                $row->HEAD_COUNT,
                $row->PLANTING_YEAR
            );
        }

        return $wineRows;
    }
}