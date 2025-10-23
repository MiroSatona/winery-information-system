<?php

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use Nette\Utils\DateTime;


class Bottling extends Task
{
    private ?int $idWine;
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

        $this->idWine = $idWine;
        $this->bottledVolume = $bottlesCount * $bottleVolume;
        $this->sugarContent = null;
    }

    public function getIdWine(): ?int
    {
        return $this->idWine;
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