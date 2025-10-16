<?php 

namespace App\Model;

use Nette\Database\Explorer;
use App\Model\Wine;

final class ProductRepository
{
    private Explorer $database;

    public function __construct(Explorer $database)
    {
       $this->database = $database; 
    }
    
    public function getAllProducts(): array 
    {
        $rows = $this->database->table("WINE");
        
        // create the wine class for ease of use 
        $wines = [];
        foreach ($rows as $row) {
            $wines[] = new Wine(
                id: (int)$row->ID,
                idLiquidator: $row->ID_LIQUIDATOR,
                idSellApprover: $row->ID_SELL_APPROVER,
                idPicture: $row->ID_PICTURE,
                year: (int)$row->YEAR,
                alcoholPercentage: (int)$row->ALCOHOL_PERCENTAGE,
                type: (string)$row->TYPE,
                color: (string)$row->COLOR,
                grade: (string)$row->GRADE,
                state: (string)$row->STATE,
                volume: (int)$row->VOLUME,
                bottlePrice: $row->BOTTLE_PRICE,
                currentPrice: $row->CURRENT_PRICE,
                bottlesLeft: $row->BOTTLES_LEFT,
                leftSugar: $row->LEFT_SUGAR,
                acidContents: $row->ACID_CONTENTS,
                salesDescription: $row->SALES_DESCRIPTION,
                productionNote: (string)$row->PRODUCTION_NOTE
            );
        }
        
        return $wines;
    }
}