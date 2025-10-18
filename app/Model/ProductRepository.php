<?php 

namespace App\Model;

use Nette\Database\Explorer;
use App\Model\Wine\Wine;
use App\Model\Wine\WineRepository;
use App\Model\Wine\WineState;

final class ProductRepository
{
    public function __construct(private Explorer $database, private WineRepository $wineRep)
    {
       $this->database = $database; 
    }
    
    public function getAllProducts(): array 
    {
        $result = $this->database->table('WINE')
        ->where("STATE", WineState::sale->value)
        ->where("BOTTLES_LEFT > ?", 0)
        ->fetchAll();
       
        $wines = [];
        foreach ($result as $row) {
            $wine = $this->wineRep->createWineFromRow($row);
            $wines[] = $wine;
        }
        return $wines;
    }
    
    public function findById(int $id): ?Wine 
    {
        return $this->wineRep->fetchWineById($id);
    }
}