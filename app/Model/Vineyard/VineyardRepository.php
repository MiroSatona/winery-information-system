<?php

declare(strict_types=1);

namespace App\Model\Vineyard;

use App\Model\Image;
use Tracy\Debugger;
use Tracy\ILogger;
use Nette\Database\Explorer;


final class VineyardRepository
{
    public function __construct(
        private Explorer $database
    ) {
    }

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
                $row->NAME,
                $row->LOCATION,
                $image,
                $row->DESCRIPTION
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
            $row->NAME,
            $row->LOCATION,
            $image,
            $row->DESCRIPTION
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
                    'DESCRIPTION' => $vineyard->getDescription()
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
                    return false;
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
                    'DESCRIPTION' => $vineyard->getDescription()
                ]);
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to update vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $vineyard->getId(), ILogger::ERROR);
            return false;
        }
    }

    public function deleteVineyard(int $id): bool
    {
        try {
            return $this->database->transaction(function () use ($id) {
                $row = $this->database->table('VINEYARD')->get($id);
                if ($row === null) {
                    return false;
                }
                $pictureId = $row->ID_PICTURE;
                $row->delete();
                // Delete picture as well
                if ($pictureId !== null) {
                    $this->database->table('PICTURE')->where('ID', $pictureId)->delete();
                }
                return true;
            });
        } catch (\Exception $e) {
            Debugger::log('Failed to delete vineyard: ' . $e->getMessage() . ' | Vineyard ID: ' . $id, ILogger::ERROR);
            return false;
        }
    }
}