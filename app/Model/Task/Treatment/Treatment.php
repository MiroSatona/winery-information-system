<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Task\Treatment\TreatmentType;
use App\Model\Vineyard\Vineyard;
use Nette\Utils\DateTime;


final class Treatment extends Task
{

    private TreatmentType $type;
    private Vineyard $vineyard;
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
        Vineyard $vineyard,
        TreatmentType $type,
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

        $this->vineyard = $vineyard;
        $this->type = $type;
        $this->nameTreatment = $nameTreatment;
        $this->quantity = $quantity;
        $this->unit = $unit;
    }

    public function getType(): TreatmentType
    {
        return $this->type;
    }

    public function getTypeText(): string
    {
        switch ($this->type) {
            case TreatmentType::CUTTING:
                return 'Řez';
            case TreatmentType::SPRAYING:
                return 'Postřik';
            case TreatmentType::FERTILIZING:
                return 'Hnojení';
            case TreatmentType::WEEDING:
                return 'Odplevelení';
            default:
                return 'Neznámý typ';
        }
    }

    public function getVineyard(): Vineyard
    {
        return $this->vineyard;
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