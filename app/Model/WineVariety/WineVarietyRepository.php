<?php
//FILE:     WineVarietyRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for tasks related to wine varieties (DB operations)

declare(strict_types=1);

namespace App\Model\WineVariety;

use App\Model\Image\Image;
use Nette\Database\Explorer;
use App\Model\WineVariety\WineVariety;
use App\Model\Image\ImageRepository;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Repository for tasks related to wine varieties
 * 
 * Handles CRUD operations on the DB for wine varieties, as well as other operations.
 */
final class WineVarietyRepository
{
    /**
     * Repository constructor
     * 
     * Arguments provided by Nette DI container
     *
     * @param Explorer $database
     * @param ImageRepository $imageRepository
     */
    public function __construct(
        private Explorer $database,
        private ImageRepository $imageRepository
    ) {
    }

    /**
     * Fetch all wine varieties from the database
     * 
     * @return WineVariety[]
     */
    public function fetchAll(): array
    {
        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = [];
        $rows = $this->database->table('WINE_VARIETY')->fetchAll();
        
        // assemble WineVariety objects one by one
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
            
            $wineVarieties[] = new WineVariety(
                $row->ID,
                $row->NAME,
                WineVarietyType::from($row->COLOR),
                $image,
                $row->DESCRIPTION
            );
        }
        return $wineVarieties;
    }
    
    /**
     * Fetch single wine variety by its ID
     * 
     * @param int $id
     * @return WineVariety|null
     */
    public function fetchById(int $id): ?WineVariety
    {
        $row = $this->database->table('WINE_VARIETY')->get($id);
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

        return new WineVariety(
            $row->ID,
            $row->NAME,
            WineVarietyType::from($row->COLOR),
            $image,
            $row->DESCRIPTION
        );
    }

    /**
     * Check whether Wine Variety with given id has been used or not
     *
     * @param integer $id
     * @return boolean
     */
    public function checkWineVarietyUsed(int $id): bool
    {
        $count = $this->database->table('WINE_ROW')
            ->where('ID_WINE_VARIETY', $id)
            ->count();
       
        $count += $this->database->table("HARVEST")
            ->where('ID_WINE_VARIETY', $id)
            ->count();
        
        return $count > 0;
    }

    /**
     * Add given Wine Variety to the database
     *
     * Return value indicates success or failure
     * 
     * @param WineVariety $wineVariety
     * @return boolean
     */
    public function addWineVariety(WineVariety $wineVariety): bool
    {
        try { // run everything in transaction
            return $this->database->transaction(function () use ($wineVariety) {
                // handle picture insertion
                $pictureId = null;
                $image = $wineVariety->getImage();
                if ($image !== null) {
                    $pictureData = [
                        'LINK' => $image->src,
                        'IMAGE_NAME' => $image->name,
                        'DESCRIPTION' => $image->description
                    ];
                    $picture = $this->database->table('PICTURE')->insert($pictureData);
                    $pictureId = $picture->ID;
                }
                // insert wine variety
                $data = [
                    'NAME' => $wineVariety->getName(),
                    'COLOR' => $wineVariety->getColor()->value,
                    'ID_PICTURE' => $pictureId,
                    'DESCRIPTION' => $wineVariety->getDescription()
                ];
                $this->database->table('WINE_VARIETY')->insert($data);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to add wine variety: ' . $e->getMessage() . ' | Wine: ' . $wineVariety->getName(), ILogger::ERROR);
            return false;
        }
    }

    /**
     * Update given Wine Variety in the database
     *
     * Return value indicates success or failure
     * 
     * @param WineVariety $wineVariety
     * @return boolean
     */
    public function updateWineVariety(WineVariety $wineVariety): bool
    {
        try { // run everything in transaction
            return $this->database->transaction(function () use ($wineVariety) {
                $row = $this->database->table('WINE_VARIETY')->get($wineVariety->getId());
                if ($row === null) {
                    throw new \Exception('Wine variety not found with ID ' . $wineVariety->getId());
                }

                // handle picture update
                $pictureId = $row->ID_PICTURE;
                $image = $wineVariety->getImage();
                
                if ($image !== null) {
                    if ($pictureId !== null) {
                        if ($image->id !== $pictureId) {
                            // delete old image data
                            $this->imageRepository->deleteImageFromUploads($row->ref('PICTURE')->LINK);
                            // update existing picture
                            $this->database->table('PICTURE')->get($pictureId)->update([
                                'LINK' => $image->src,
                                'IMAGE_NAME' => $image->name,
                                'DESCRIPTION' => $image->description
                            ]);
                        }
                    } else {
                        // create new picture
                        $pictureData = [
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ];
                        $picture = $this->database->table('PICTURE')->insert($pictureData);
                        $pictureId = $picture->ID;
                    }
                } else {
                    // remove reference and delete from database
                    if ($pictureId !== null) {
                        $this->imageRepository->removeImage($pictureId);
                    }
                    $pictureId = null;
                }

                // handle wine variety update
                $data = [
                    'NAME' => $wineVariety->getName(),
                    'COLOR' => $wineVariety->getColor()->value,
                    'ID_PICTURE' => $pictureId,
                    'DESCRIPTION' => $wineVariety->getDescription()
                ];
                
                $row->update($data);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update wine variety: ' . $e->getMessage() . ' | ID: ' . $wineVariety->getId() . ' | Wine: ' . $wineVariety->getName(), ILogger::ERROR);
            return false;
        }
    }

    /**
     * Delete wine variety with given ID from the database
     *
     * Return value indicates success or failure
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteWineVariety(int $id): bool
    {
        try { // run everything in transaction
            return $this->database->transaction(function () use ($id) {
                $row = $this->database->table('WINE_VARIETY')->get($id);
                if ($row === null) {
                    throw new \Exception('Wine variety not found with ID ' . $id);
                }

                $pictureId = $row->ID_PICTURE;
                
                // delete wine variety record
                $row->delete();
                
                // delete picture as well
                if ($pictureId !== null) {
                    $this->imageRepository->removeImage($pictureId);

                }
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to delete wine variety: ' . $e->getMessage() . ' | ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }
}
