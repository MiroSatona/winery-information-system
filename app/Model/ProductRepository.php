<?php 

namespace App\Model;

use Nette\Database\Explorer;
use App\Model\Wine\Wine;
use App\Model\Wine\WineRepository;

final class ProductRepository
{
    public function __construct(private Explorer $database, private WineRepository $wineRep)
    {
       $this->database = $database; 
    }
    
    public function getAllProducts(): array 
    {
        return $this->wineRep->fetchAllWines();
    }
    
    public function findById(int $id): ?Wine 
    {
        return $this->wineRep->fetchWineById($id);
    }
}