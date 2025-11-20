<?php
//FILE:     Treatment.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Internal representation of a treatment task.

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use App\Model\Task\Task;
use App\Model\Task\TaskState;
use App\Model\Task\Treatment\TreatmentType;
use App\Model\Vineyard\Vineyard;
use Nette\Utils\DateTime;

/**
 * Represents a Treatment Task entity
 */
final class Treatment extends Task
{
    private TreatmentType $type; // type of treatment
    private Vineyard $vineyard; // associated vineyard
    private string $nameTreatment; // name of the treatment
    private ?float $quantity; // quantity used in treatment
    private ?string $unit; // unit of the quantity

    /**
     * Treatment constructor
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
     * @param TreatmentType $type
     * @param string $nameTreatment
     * @param float|null $quantity
     * @param string|null $unit
     */
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
        ?float $quantity,
        ?string $unit
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

    /**
     * Get the treatment type
     *
     * @return TreatmentType
     */
    public function getType(): TreatmentType
    {
        return $this->type;
    }

    /**
     * Get the treatment type as human-readable text
     *
     * @return string
     */
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

    /**
     * Get the vineyard associated with this treatment task
     *
     * @return Vineyard
     */
    public function getVineyard(): Vineyard
    {
        return $this->vineyard;
    }

    /**
     * Get the name of the treatment
     *
     * @return string
     */
    public function getNameTreatment(): string
    {
        return $this->nameTreatment;
    }

    /**
     * Get the quantity used in treatment
     *
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * Get the unit of the quantity
     *
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }
}
