<?php

//FILE:     Harvest.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a harvest task.
declare(strict_types=1);

namespace App\Model\Task\Harvest;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Vineyard\Vineyard;
use App\Model\WineVariety\WineVariety;
use Nette\Utils\DateTime;

/**
 * Represents a Harvest Task entity
 */
final class Harvest extends Task
{
    /**
     * Harvest constructor
     *
     * @param int $id
     * @param string $name
     * @param TaskState $state
     * @param DateTime $creationTimestamp
     * @param DateTime $lastChangeTimestamp
     * @param DateTime|null $plannedTimestamp
     * @param DateTime|null $executionTimestamp
     * @param string $note
     * @param int|null $idTaskIssuer
     * @param int|null $idTaskConfirmer
     * @param Vineyard $vineyard
     * @param WineVariety $wineVariety
     * @param int|null $idHarvestConfirmer
     * @param int|null $idWine
     * @param float|null $weight
     * @param float|null $sugarContent
     */
    public function __construct(
        int $id,
        string $name,
        TaskState $state,
        DateTime $creationTimestamp,
        DateTime $lastChangeTimestamp,
        ?DateTime $plannedTimestamp,
        ?DateTime $executionTimestamp,
        string $note,
        ?int $idTaskIssuer,
        ?int $idTaskConfirmer,
        private Vineyard $vineyard,
        private WineVariety $wineVariety,
        private ?int $idHarvestConfirmer,
        private ?int $idWine,
        private ?float $weight,
        private ?float $sugarContent
    ) {
        parent::__construct(
            $id,
            $name,
            $state,
            $creationTimestamp,
            $lastChangeTimestamp,
            $plannedTimestamp,
            $executionTimestamp,
            $note,
            $idTaskIssuer,
            $idTaskConfirmer
        );
    }

    /**
     * Get the vineyard associated with this harvest task
     *
     * @return Vineyard
     */
    public function getVineyard(): Vineyard
    {
        return $this->vineyard;
    }

    /**
     * Get the wine variety associated with this harvest task
     *
     * @return WineVariety
     */
    public function getWineVariety(): WineVariety
    {
        return $this->wineVariety;
    }

    /**
     * Get the ID of the harvest confirmer
     *
     * @return int|null
     */
    public function getIdHarvestConfirmer(): ?int
    {
        return $this->idHarvestConfirmer;
    }

    /**
     * Get the ID of the wine associated with this harvest
     *
     * @return int|null
     */
    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    /**
     * Get the weight of the harvest
     *
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * Get the sugar content of the harvest
     *
     * @return float|null
     */
    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    /**
     * Set the ID of the wine associated with this harvest
     *
     * @param int $idWine
     * @return void
     */
    public function setIdWine(int $idWine): void
    {
        $this->idWine = $idWine;
    }

    /**
     * Set the vineyard for this harvest task
     *
     * @param Vineyard $vineyard
     * @return void
     */
    public function setVineyard(Vineyard $vineyard): void
    {
        $this->vineyard = $vineyard;
    }

    /**
     * Set the wine variety for this harvest task
     *
     * @param WineVariety $wineVariety
     * @return void
     */
    public function setWineVariety(WineVariety $wineVariety): void
    {
        $this->wineVariety = $wineVariety;
    }

    /**
     * Set the ID of the harvest confirmer
     *
     * @param int $idHarvestConfirmer
     * @return void
     */
    public function setIdHarvestConfirmer(int $idHarvestConfirmer): void
    {
        $this->idHarvestConfirmer = $idHarvestConfirmer;
    }

    /**
     * Set the weight of the harvest
     *
     * @param float|null $weight
     * @return void
     */
    public function setWeight(?float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * Set the sugar content of the harvest
     *
     * @param float|null $sugarContent
     * @return void
     */
    public function setSugarContent(?float $sugarContent): void
    {
        $this->sugarContent = $sugarContent;
    }
}
