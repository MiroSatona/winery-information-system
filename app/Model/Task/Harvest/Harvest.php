<?php

declare(strict_types=1);

namespace App\Model\Task\Harvest;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Vineyard\Vineyard;
use App\Model\WineVariety\WineVariety;
use Nette\Utils\DateTime;


final class Harvest extends Task
{
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

    public function getVineyard(): Vineyard
    {
        return $this->vineyard;
    }

    public function getWineVariety(): WineVariety
    {
        return $this->wineVariety;
    }

    public function getIdHarvestConfirmer(): ?int
    {
        return $this->idHarvestConfirmer;
    }

    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    public function setIdWine(int $idWine): void
    {
        $this->idWine = $idWine;
    }

    public function setVineyard(Vineyard $vineyard): void
    {
        $this->vineyard = $vineyard;
    }

    public function setWineVariety(WineVariety $wineVariety): void
    {
        $this->wineVariety = $wineVariety;
    }

    public function setIdHarvestConfirmer(int $idHarvestConfirmer): void
    {
        $this->idHarvestConfirmer = $idHarvestConfirmer;
    }

    public function setWeight(?float $weight): void
    {
        $this->weight = $weight;
    }

    public function setSugarContent(?float $sugarContent): void
    {
        $this->sugarContent = $sugarContent;
    }
}