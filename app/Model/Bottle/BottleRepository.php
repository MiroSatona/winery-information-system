<?php

namespace App\Model\Bottle;

use Nette\Database\Explorer;
use App\Model\Bottle\Bottle;

final class BottleRepository 
{
    public function __construct(private Explorer $db)
    {
    }
    
    public function fetchByWineId(int $wineId) {
        $rows = $this->db->table("BOTTLING")->where("ID_WINE", $wineId);
        
        $results = [];
        foreach($rows as $row) {
            $results[] = $this->fromRow($row);
        }
        return $results;
    }
    
    private function fromRow($row) {
        return new Bottle(
            $row->ID_TASK,
            $row->ID_WINE,
            $row->BOTTLES_COUNT,
            $row->BOTTLE_VOLUME,
        );
    }
} 