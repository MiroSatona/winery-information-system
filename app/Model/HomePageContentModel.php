<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Database\Explorer;
use Nette\Database\Row;

/**
 * @HomePageContentModel
 * Model for managing the get home page content from the database.
 */
class HomePageContentModel{

    /** @var Explorer $database Database instance. */
    private Explorer $database;
    /** @var string $queryWineYards SQL query for fetching wine yards and information about them. */
    private string $queryWineYards;
    /** @var string $queryWineVarieties SQL query for fetching wine varieties from the database. */
    private string $queryWineVarieties;

    /**
     * Constructor for HomePageContentModel.
     *
     * @param Explorer $database Database instance.
     */
    public function __construct(Explorer $database) {
        $this->database = $database;
        $this->queryWineYards = 'SELECT V.ID, V.NAME, V.DESCRIPTION,
                                P.ID          AS PICTURE_ID,
                                P.IMAGE_NAME  AS PICTURE_NAME,
                                P.LINK        AS PICTURE_LINK,
                                GROUP_CONCAT(DISTINCT WV.NAME ORDER BY WV.NAME SEPARATOR ", ") AS varietiesText
                                FROM VINEYARD AS V
                                LEFT JOIN PICTURE       AS P  ON P.ID = V.ID_PICTURE
                                LEFT JOIN WINE_ROW      AS WR ON WR.ID_VINEYARD = V.ID
                                LEFT JOIN WINE_VARIETY  AS WV ON WV.ID = WR.ID_WINE_VARIETY
                                GROUP BY
                                    V.ID, V.NAME, V.DESCRIPTION,
                                    P.ID, P.IMAGE_NAME, P.LINK
                                ORDER BY V.NAME;';
                                
        $this->queryWineVarieties = 'SELECT WV.ID, WV.NAME, WV.COLOR, WV.DESCRIPTION,
                                    P.ID         AS PICTURE_ID,
                                    P.IMAGE_NAME AS PICTURE_NAME,
                                    P.LINK       AS PICTURE_LINK
                                    FROM WINE_VARIETY AS WV
                                    LEFT JOIN PICTURE AS P ON P.ID = WV.ID_PICTURE
                                    ORDER BY WV.NAME';
    }

    /**
     * Getter for wine yards content for the home page.
     * This method will execute the Sql query to fetch the wine yards, with their information, wine varieties and pictures.
     * 
     * @return Row[] Array of rows containing wine yards content or empty if this data is not found.
     */
    public function getWineYardsContent(): array{
        return $this->database->query($this->queryWineYards)->fetchAll() ?: [];
    }

    /**
     * Getter for wine varieties content for the home page.
     * This method will execute the Sql query to fetch the wine varieties with their information and pictures.
     *
     * @return Row[] Array of rows containing wine varieties content or empty if this data is not found.
     */
    public function getWineVarietiesContent(): array{
        return $this->database->query($this->queryWineVarieties)->fetchAll() ?: [];
    }
        
}