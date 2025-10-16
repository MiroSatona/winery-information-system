<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Database\Explorer;
use Nette\Database\Row;

/**
 * @HomePageContentModel
 * Model for managing the content of the home page, including vineyards and the wine varieties grown on each vineyard.
 */
class HomePageContentModel{

    /** @var Explorer $database Database instance. */
    private Explorer $database;
    /** @var string $sql_query SQL query for fetching home page content. */
    private string $sql_query;

    /**
     * Constructor for HomePageContentModel.
     *
     * @param Explorer $database Database instance.
     */
    public function __construct(Explorer $database) {
        $this->database = $database;
        $this->sql_query = 'SELECT VINEYARD.ID, VINEYARD.NAME, VINEYARD.DESCRIPTION,
                            GROUP_CONCAT(
                                DISTINCT WINE_VARIETY.NAME 
                                ORDER BY WINE_VARIETY.NAME 
                                SEPARATOR ", "
                            ) AS varietiesText
                            FROM VINEYARD
                            LEFT JOIN PLANTING 
                                ON PLANTING.ID_VINEYARD = VINEYARD.ID
                            LEFT JOIN WINE_VARIETY 
                                ON WINE_VARIETY.ID = PLANTING.ID_WINE_VARIETY
                            GROUP BY 
                                VINEYARD.ID, 
                                VINEYARD.NAME, 
                                VINEYARD.DESCRIPTION
                            ORDER BY 
                                VINEYARD.NAME;';
    }

    /**
     * Method getHomePageContent
     * This method is used to get the content for the home page, including vineyards and the wine varieties grown on each vineyard.
     * 
     * @return Row[] Array of vineyards with their details and associated wine varieties or an empty array if data in the database is missing.
     */
    public function getHomePageContent(): array {
        return $this->database->query($this->sql_query)->fetchAll() ?: [];
        
    }
        
}