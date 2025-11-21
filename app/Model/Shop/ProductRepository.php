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

/**
 * Repository of products, allows for searching in products
 */
final class ProductRepository
{
    
    /**
     * Constructor for Product repository
     *
     * @param Explorer $database
     * @param WineRepository $wineRep
     */
    public function __construct(private Explorer $database, private WineRepository $wineRep)
    {
        $this->database = $database; 
    }
   
    /**
     * Get all the available products with an option for a search query
     * 
     * @param string $searchTerm
     * @return array
     */
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

    /**
     * Find a product by product ID
     * 
     * @param integer $id
     * @return Wine|null
     */
    public function findById(int $id): ?Wine 
    {
        return $this->wineRep->fetchById($id);
    }
}