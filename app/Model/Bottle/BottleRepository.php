<?php
//FILE:     BottleRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for managing operations with bottles in the system.

namespace App\Model\Bottle;

use Nette\Database\Explorer;
use App\Model\Bottle\Bottle;

/**
 * Handles DB operations related to Bottles
 */
final class BottleRepository 
{
    /**
     * Repository constructor
     *
     * @param Explorer $db
     */
    public function __construct(private Explorer $db)
    {
    }
    
    /**
     * Fetch all bottles for a given wine ID
     *
     * @param int $wineId
     * @return Bottle[]
     */
    public function fetchByWineId(int $wineId) {
        $rows = $this->db->table("BOTTLING")->where("ID_WINE", $wineId);
        
        $results = [];
        foreach($rows as $row) {
            $results[] = $this->fromRow($row);
        }
        return $results;
    }
    
    /**
     * Create a Bottle object from a DB row
     *
     * @param \Nette\Database\Table\ActiveRow $row
     * @return Bottle
     */
    private function fromRow($row) {
        return new Bottle(
            $row->ID_TASK,
            $row->ID_WINE,
            $row->BOTTLES_COUNT,
            $row->BOTTLE_VOLUME,
        );
    }
} 