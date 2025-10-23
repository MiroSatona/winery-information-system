<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use Nette\Utils\DateTime;


final class Treatment extends Task
{

    private string $category;
    private string $nameTreatment;
    private float $quantity;
    private string $unit;

    public function __construct(
        private int $id,
        private string $name,
        private TaskState $state,
        private DateTime $creationTimestamp,
        private DateTime $lastChangeTimestamp,
        private ?DateTime $plannedTimestamp,
        private ?DateTime $executionTimestamp,
        private string $note,
        private ?int $idTaskIssuer,
        private ?int $idTaskConfirmer,
        string $category,
        string $nameTreatment,
        float $quantity,
        string $unit
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

        $this->category = $category;
        $this->nameTreatment = $nameTreatment;
        $this->quantity = $quantity;
        $this->unit = $unit;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getNameTreatment(): string
    {
        return $this->nameTreatment;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }


    public function getUnit(): string
    {
        return $this->unit;
    }
}