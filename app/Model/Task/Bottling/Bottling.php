<?php

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Wine\Wine;
use Nette\Utils\DateTime;


class Bottling extends Task
{
    private ?int $idWine;
    private int $bottlesCount;
    private float $bottleVolume;
    private ?float $bottledVolume;
    private ?float $sugarContent;
    private Wine $wine;

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
    )
    {
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

    public function getWine(): Wine
    {
        return $this->wine;
    }

    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    public function getBottledVolume(): ?float
    {
        return $this->bottledVolume;
    }

    public function getBottlesCount(): int
    {
        return $this->bottlesCount;
    }

    public function getBottleVolume(): float
    {
        return $this->bottleVolume;
    }

    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    
}