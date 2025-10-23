<?php

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\User\User;
use App\Model\Vineyard\Vineyard;
use App\Model\WineVariety\WineVariety;
use App\Model\Wine\Wine;
use Nette\Utils\DateTime;


class Bottling extends Task
{
    private int $key;
    private Vineyard $vineyard;
    private WineVariety $wineVariety;
    private ?User $bottlingConfirmer;
    private ?Wine $wine;
    private ?float $bottledVolume;
    private ?float $sugarContent;

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
        Vineyard $vineyard,
        WineVariety $wineVariety,
        ?int $idHarvestConfirmer,
        ?int $idWine,
        ?float $weight,
        ?float $sugarContent
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

        $this->vineyard = $vineyard;
        $this->wineVariety = $wineVariety;
        $this->bottlingConfirmer = $idHarvestConfirmer;
        $this->wine = $idWine;
        $this->bottledVolume = $weight;
        $this->sugarContent = $sugarContent;
    }

    public function getVineyard(): Vineyard
    {
        return $this->vineyard;
    }

    public function getWineVariety(): WineVariety
    {
        return $this->wineVariety;
    }
    public function getBottlingConfirmer(): ?User
    {
        return $this->bottlingConfirmer;
    }

    public function getWine(): ?Wine
    {
        return $this->wine;
    }

    public function getBottledVolume(): ?float
    {
        return $this->bottledVolume;
    }

    public function getSugarContent(): ?float
    {
        return $this->sugarContent;
    }

    
}