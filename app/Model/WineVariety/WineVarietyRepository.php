<?php

declare(strict_types=1);

namespace App\Model\WineVariety;

use App\Model\Image\Image;
use Nette\Database\Explorer;
use App\Model\WineVariety\WineVariety;
use Tracy\Debugger;
use Tracy\ILogger;

final class WineVarietyRepository
{
    public function __construct(
        private Explorer $database
    ) {
    }

    /**
     * @return WineVariety[]
     */
    public function fetchAll(): array
    {
        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = [];
        $rows = $this->database->table('WINE_VARIETY')->fetchAll();
            
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
    
    public function fetchById(int $id): ?WineVariety
    {
        $row = $this->database->table('WINE_VARIETY')->get($id);
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

        return new WineVariety(
            $row->ID,
            $row->NAME,
            WineVarietyType::from($row->COLOR),
            $image,
            $row->DESCRIPTION
        );
    }

    public function addWine(WineVariety $wineVariety): bool
    {
        try {
            return $this->database->transaction(function () use ($wineVariety) {
                // Handle picture insertion
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
                // Insert wine variety
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

    public function updateWine(WineVariety $wineVariety): bool
    {
        try {
            return $this->database->transaction(function () use ($wineVariety) {
                $row = $this->database->table('WINE_VARIETY')->get($wineVariety->getId());
                if ($row === null) {
                    return false;
                }

                // Handle picture update
                $pictureId = $row->ID_PICTURE;
                $image = $wineVariety->getImage();
                
                if ($image !== null) {
                    if ($pictureId !== null) {
                        // Update existing picture
                        $this->database->table('PICTURE')->get($pictureId)->update([
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ]);
                    } else {
                        // Create new picture
                        $pictureData = [
                            'LINK' => $image->src,
                            'IMAGE_NAME' => $image->name,
                            'DESCRIPTION' => $image->description
                        ];
                        $picture = $this->database->table('PICTURE')->insert($pictureData);
                        $pictureId = $picture->ID;
                    }
                } else {
                    // Remove reference and delete from database
                    if ($pictureId !== null) {
                        $this->database->table('PICTURE')->get($pictureId)->delete();
                    }
                    $pictureId = null;
                }

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

    public function deleteWine(int $id): bool
    {
        try {
            return $this->database->transaction(function () use ($id) {
                $row = $this->database->table('WINE_VARIETY')->get($id);
                if ($row === null) {
                    return false;
                }

                $pictureId = $row->ID_PICTURE;
                
                // Delete wine variety record
                $row->delete();
                
                // Delete picture as well
                if ($pictureId !== null) {
                    $this->database->table('PICTURE')->get($pictureId)->delete();
                }
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to delete wine variety: ' . $e->getMessage() . ' | ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }


}