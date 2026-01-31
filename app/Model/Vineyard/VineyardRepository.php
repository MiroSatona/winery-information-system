<?php

//FILE:     VineyardRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for managing Vineyard entities

declare(strict_types=1);

namespace App\Model\Vineyard;

use App\Model\Image\Image;
use App\Model\Image\ImageRepository;
use App\Model\WineRow\WineRow;
use App\Model\WineVariety\WineVariety;
use App\Model\WineVariety\WineVarietyRepository;
use Tracy\Debugger;
use Tracy\ILogger;
use Nette\Database\Explorer;

/**
 * Handles Vineyard related operations with the database
 */
final class VineyardRepository
{
    /**
     * VineyardRepository constructor
     *
     * @param Explorer $database
     * @param WineVarietyRepository $wineVarietyRepository
     * @param ImageRepository $imageRepository
     */
    public function __construct(
        private Explorer $database,
        private WineVarietyRepository $wineVarietyRepository,
        private ImageRepository $imageRepository
    ) {
    }

    /**
     * Fetch all vineyards from the database
     *
     * @return Vineyard[]
     */
    public function fetchAll(): array
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = [];
        $rows = $this->database->table('VINEYARD')->fetchAll();

        // build Vineyard objects
        foreach ($rows as $row) {
            // access PICTURE data by left join
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

    /**
     * Fetch a vineyard by its ID
     *
     * @param int $id
     * @return Vineyard|null
     */
    public function fetchById(int $id): ?Vineyard
    {
        $row = $this->database->table('VINEYARD')->get($id);
        if ($row === null) {
            return null;
        }

        // access PICTURE data by left join
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
     * Fetch all vineyards matching a pattern in name or location
     *
     * @param string $pattern
     * @param string $type 'all', 'active', 'inactive'
     * @return Vineyard[]
     */
    public function fetchAllWithPattern(string $pattern, string $type): array
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = [];
        // filter by pattern (name, location)
        $rowsSelection = $this->database->table('VINEYARD')
            ->where('NAME LIKE ? OR LOCATION LIKE ?', "%$pattern%", "%$pattern%");

        // filter by type
        if ($type === 'active') {
            $rowsSelection = $rowsSelection->where('ACTIVE', true);
        } elseif ($type === 'inactive') {
            $rowsSelection = $rowsSelection->where('ACTIVE', false);
        }

        $rows = $rowsSelection->fetchAll();

        // build Vineyard objects
        foreach ($rows as $row) {
            // access PICTURE data by left join
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

    /**
     * Add a new vineyard to the database
     *
     * @param Vineyard $vineyard
     * @return bool True on success, false on failure
     */
    public function addVineyard(Vineyard $vineyard): bool
    {
        try { // run within transaction
            return $this->database->transaction(function () use ($vineyard) {
                // handle picture insertion
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
                // insert vineyard record
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

    /**
     * Update an existing vineyard in the database
     *
     * @param Vineyard $vineyard
     * @return bool True on success, false on failure
     */
    public function updateVineyard(Vineyard $vineyard): bool
    {
        try { // run within transaction
            return $this->database->transaction(function () use ($vineyard) {
                $row = $this->database->table('VINEYARD')->get($vineyard->getId());
                if ($row === null) {
                    throw new \Exception('Vineyard not found with ID ' . $vineyard->getId());
                }
                // handle picture update or insertion
                $pictureId = $row->ID_PICTURE;
                $image = $vineyard->getImage();

                if ($image !== null) {
                    if ($pictureId !== null) {
                        // do not change image if it stayed the same
                        if ($image->id !== $pictureId) {
                            // delete previous image data
                            $this->imageRepository->deleteImageFromUploads($row->ref('PICTURE')->LINK);
                            // update existing picture
                            $this->database->table('PICTURE')->where('ID', $pictureId)->update([
                                'LINK' => $image->src,
                                'IMAGE_NAME' => $image->name,
                                'DESCRIPTION' => $image->description
                            ]);
                        }
                    } else {
                        // or insert new picture
                        $pictureData = [
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ];
                        $picture = $this->database->table('PICTURE')->insert($pictureData);
                        $pictureId = $picture->ID;
                    }
                } else {
                    // if no image provided, remove existing picture reference
                    if ($pictureId !== null) {
                        $this->imageRepository->removeImage($pictureId);
                    }
                    $pictureId = null;
                }
                // update vineyard record
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

    /**
     * Set the active status of a vineyard
     *
     * @param int $id
     * @param bool $status
     * @return bool True on success, false on failure
     */
    public function setVineyardActiveStatus(int $id, bool $status): bool
    {
        try { // run within transaction
            return $this->database->transaction(function () use ($id, $status) {
                $row = $this->database->table('VINEYARD')->get($id);
                if ($row === null) {
                    throw new \Exception('Vineyard not found with ID ' . $id);
                }
                $row->update(['ACTIVE' => $status]);

                // also set all associated wine rows
                $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $id)
                    ->update(['ACTIVE' => $status]);

                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to set vineyard active status: ' . $e->getMessage() . ' | Vineyard ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }

    /**
     * Join vineyards with their wine varieties
     *
     * @return array Array of vineyards with their associated wine varieties
     */
    public function joinWithVarieties(): array
    {

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

    /**
     * Get all wine rows on a given vineyard
     *
     * @param int $vineyardId
     * @return WineRow[]
     */
    public function getWineRowsOnVineyard(int $vineyardId): array
    {
        /** @var WineRow[] $wineRows Array of wine rows on the given vineyard */
        $wineRows = [];
        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->fetchAll();

        // build WineRow objects
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

    /**
     * Get wine rows on a given vineyard matching a pattern
     *
     * @param int $vineyardId
     * @param string $pattern
     * @param string $type 'all', 'active', 'inactive'
     * @return WineRow[]
     */
    public function getWineRowsOnVineyardWithPattern(int $vineyardId, string $pattern, string $type): array
    {
        /** @var WineRow[] $wineRows Array of wine rows on the given vineyard */
        $wineRows = [];
        // fetch wine rows matching the pattern in CODE, PLANTING_YEAR, or associated WINE_VARIETY name
        $rowsSelection = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('CODE LIKE ? OR PLANTING_YEAR LIKE ? OR ID_WINE_VARIETY IN ?', "%$pattern%", "%$pattern%", $this->database->table('WINE_VARIETY')->where('NAME LIKE ?', "%$pattern%")->select('ID'));

        // filter by type
        if ($type === 'active') {
            $rowsSelection = $rowsSelection->where('ACTIVE', true);
        } elseif ($type === 'inactive') {
            $rowsSelection = $rowsSelection->where('ACTIVE', false);
        }

        $rows = $rowsSelection->fetchAll();

        // build WineRow objects
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

    /**
     * Get a specific wine row on a vineyard by its code
     *
     * @param int $vineyardId
     * @param int $wineRowCode
     * @return WineRow|null
     */
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

    /**
     * Get unique wine varieties planted on a given vineyard
     *
     * @param int $vineyardId
     * @return WineVariety[]
     */
    public function getUniqueWineVarietiesOnVineyard(int $vineyardId): array
    {
        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = [];

        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->select('DISTINCT ID_WINE_VARIETY')
            ->fetchAll();

        // fetch each unique variety
        foreach ($rows as $row) {
            $variety = $this->wineVarietyRepository->fetchById($row->ID_WINE_VARIETY);
            if ($variety) {
                $wineVarieties[] = $variety;
            }
        }

        return $wineVarieties;
    }

    /**
     * Get count of unique wine varieties on a vineyard
     *
     * @param int $vineyardId
     * @return int
     */
    public function getWineVarietiesCountOnVineyard(int $vineyardId): int
    {
        return $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->select('DISTINCT ID_WINE_VARIETY')
            ->count();
    }

    /**
     * Set active status of a given wine row
     *
     * @param int $vineyardId
     * @param int $wineRowCode
     * @param bool $active
     * @return bool True on success, false on failure
     */
    public function setWineRowActiveStatus(int $vineyardId, int $wineRowCode, bool $active): bool
    {
        try { // run within transaction
            return $this->database->transaction(function () use ($vineyardId, $wineRowCode, $active) {
                $row = $this->database->table('WINE_ROW')
                    ->where('ID_VINEYARD', $vineyardId)
                    ->where('CODE', $wineRowCode)
                    ->fetch();
                if ($row === null) {
                    throw new \Exception('Wine row not found on vineyard: ' . $vineyardId . ' with code: ' . $wineRowCode);
                }
                $row->update(['ACTIVE' => $active]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to set wine row active status on vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $vineyardId . ' | Wine Row Code: ' . $wineRowCode, ILogger::ERROR);
            return false;
        }
    }

    /**
     * Get the wine variety of a specific wine row on a vineyard
     *
     * @param int $vineyardId
     * @param int $wineRowCode
     * @return WineVariety|null
     */
    public function getWineVarietyForWineRow(int $vineyardId, int $wineRowCode): ?WineVariety
    {
        $row = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('CODE', $wineRowCode)
            ->fetch();

        if ($row === null) {
            return null;
        }

        return $this->wineVarietyRepository->fetchById($row->ID_WINE_VARIETY);
    }

    /**
     * Add a new wine row to a vineyard
     *
     * @param WineRow $wineRow
     * @return bool True on success, false on failure
     */
    public function addWineRowToVineyard(WineRow $wineRow): bool
    {
        try { // run within transaction
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

    /**
     * Update an existing wine row on a vineyard
     *
     * @param WineRow $wineRow
     * @return bool True on success, false on failure
     */
    public function updateWineRowOnVineyard(WineRow $wineRow): bool
    {
        try { // run within transaction
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

    /**
     * Get total head count of a specific wine variety on a vineyard
     *
     * @param int $vineyardId
     * @param int $wineVarietyId
     * @return int
     */
    public function getWineVarietyTotalHeadCountOnVineyard(int $vineyardId, int $wineVarietyId): int
    {
        $totalHeadCount = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('ID_WINE_VARIETY', $wineVarietyId)
            ->sum('HEAD_COUNT');

        return (int)$totalHeadCount;
    }

    /**
     * Get all wine rows of a specific wine variety on a vineyard
     *
     * @param int $vineyardId
     * @param int $wineVarietyId
     * @return WineRow[]
     */
    public function getRowsAtVineyardWithWineVariety(int $vineyardId, int $wineVarietyId)
    {
        /** @var WineRow[] $wineRows Array of wine rows matching given variety on the given vineyard */
        $wineRows = [];
        $rows = $this->database->table('WINE_ROW')
            ->where('ID_VINEYARD', $vineyardId)
            ->where('ID_WINE_VARIETY', $wineVarietyId)
            ->where('ACTIVE', true)
            ->fetchAll();

        // build WineRow objects
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
