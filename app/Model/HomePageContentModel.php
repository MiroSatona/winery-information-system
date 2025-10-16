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
        $this->sql_query = 'SELECT V.ID, V.NAME, V.DESCRIPTION,
                                GROUP_CONCAT(
                                    DISTINCT WV.NAME
                                    ORDER BY WV.NAME
                                    SEPARATOR ", "
                                ) AS varietiesText
                                FROM `VINEYARD` AS V
                                LEFT JOIN `WINE_ROW`     AS WR ON WR.ID_VINEYARD    = V.ID
                                LEFT JOIN `WINE_VARIETY` AS WV ON WV.ID             = WR.ID_WINE_VARIETY
                                GROUP BY V.ID, V.NAME, V.DESCRIPTION
                                ORDER BY V.NAME;';

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