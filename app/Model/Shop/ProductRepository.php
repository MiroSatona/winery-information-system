<?php
//FILE:     ProductRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for tasks related to products (DB operations)

namespace App\Model\Shop;

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
    
    public function getAllProducts(string $searchTerm = ''): array
    {
        // Start the query
        $query = $this->database->table('WINE')
            ->where("STATE", WineState::SALE->value)
            ->where("BOTTLES_LEFT > ?", 0);

        // If a search term is provided, apply fuzzy search
        if (!empty($searchTerm)) {
            // Add wildcards before and after for partial match
            $likeTerm = '%' . $searchTerm . '%';
            $query->where("WINE_NAME LIKE ?", $likeTerm);
        }

        // Fetch results
        $result = $query->fetchAll();

        // Convert rows into Wine objects
        $wines = [];
        foreach ($result as $row) {
            $wines[] = $this->wineRep->createWineFromRow($row);
        }

        return $wines;
    }

    public function findById(int $id): ?Wine 
    {
        return $this->wineRep->fetchWineById($id);
    }
}