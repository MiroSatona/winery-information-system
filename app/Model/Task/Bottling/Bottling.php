<?php

//FILE:     Bottling.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a bottling task.

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Wine\Wine;
use Nette\Utils\DateTime;

/**
 * Represents a Bottling Task entity
 */
class Bottling extends Task
{
    private ?int $idWine;
    private int $bottlesCount;
    private float $bottleVolume;
    private ?float $bottledVolume;
    private ?float $sugarContent;
    private Wine $wine;

    /**
     * Bottling constructor
     *
     * @param int $id
     * @param string $name
     * @param TaskState $state
     * @param DateTime $creationTimestamp
     * @param DateTime $lastChangeTimestamp
     * @param DateTime|null $plannedTimestamp
     * @param DateTime|null $executionTimestamp
     * @param Wine $wine
     * @param string $note
     * @param int|null $idTaskIssuer
     * @param int|null $idTaskConfirmer
     * @param int|null $idWine
     * @param int $bottlesCount
     * @param float $bottleVolume
     */
    public function __construct(
        int $id,
        string $name,
        TaskState $state,
        DateTime $creationTimestamp,
        DateTime $lastChangeTimestamp,
        ?DateTime $plannedTimestamp,
        ?DateTime $executionTimestamp,
        Wine $wine,
        string $note,
        ?int $idTaskIssuer,
        ?int $idTaskConfirmer,
        ?int $idWine,
        int $bottlesCount,
        float $bottleVolume
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

        $this->wine = $wine;
        $this->idWine = $idWine;
        $this->bottledVolume = $bottlesCount * $bottleVolume;
        $this->bottlesCount = $bottlesCount;
        $this->bottleVolume = $bottleVolume;
        $this->sugarContent = null;
    }

    /**
     * Get the wine associated with this bottling task
     *
     * @return Wine
     */
    public function getWine(): Wine
    {
        return $this->wine;
    }

    /**
     * Get the wine ID associated with this bottling task
     *
     * @return int|null
     */
    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    /**
     * Get the total bottled volume for this task
     *
     * @return float|null
     */
    public function getBottledVolume(): ?float
    {
        return $this->bottledVolume;
    }

    /**
     * Get the number of bottles for this task
     *
     * @return int
     */
    public function getBottlesCount(): int
    {
        return $this->bottlesCount;
    }

    /**
     * Get the volume of each bottle
     *
     * @return float
     */
    public function getBottleVolume(): float
    {
        return $this->bottleVolume;
    }

    /**
     * Get the sugar content for this bottling task
     *
     * @return float|null
     */
    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }
}
