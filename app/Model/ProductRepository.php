<?php 

namespace App\Model;

use Nette\Database\Explorer;
use App\Model\Image\Image;
use App\Model\Wine\Wine;

final class ProductRepository
{
    private Explorer $database;
    private array $allWines;

    public function __construct(Explorer $database)
    {
       $this->database = $database; 
    }
    
    public function getAllProducts(): array 
    {
        $rows = $this->database->table("WINE");
        
        $wines = [];
        
        foreach ($rows as $row) {
            $imgRow = $this->database->table("PICTURE")->get($row->ID_PICTURE);
            
            $img = new Image(
                id: (int)$imgRow->ID,
                src: (string)$imgRow->LINK,
                name: (string)$imgRow->IMAGE_NAME,
                description: (string)$imgRow->DESCRIPTION
            );

            $wines[(int)$row->ID] = new Wine(
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
                productionNote: (string)$row->PRODUCTION_NOTE,
                name: (string)$row->WINE_NAME,
                img: $img
            );
        }
        return $wines;
    }
    
    public function findById(int $id): ?Wine 
    {
        return $this->getAllProducts()[$id];
    }
}