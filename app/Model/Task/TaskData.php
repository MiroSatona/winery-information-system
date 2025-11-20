<?php
//FILE:     TaskData.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Data class for task information storage between form input stages.

declare(strict_types=1);

namespace App\Model\Task;

use App\Model\Task\TaskType;
use Nette\Utils\DateTime;
use App\Model\Image\Image;
use App\Model\Task\Treatment\TreatmentType;

/**
 * Stores task data from form inputs.
 */
class TaskData
{
    private string $name = '';
    private string $note = '';
    private ?DateTime $plannedTimestamp = null;
    private array $idsOfWorkers = []; // array of worker IDs assigned to the task
    private TaskType $taskType = TaskType::TREATMENT;

    // Harvest task
    private ?int $idWineYard = null;
    private ?int $idWineVariety = null;

    // Bottling
    private ?int $idWine = null;
    private ?float $bottleVolume = null;
    private ?Image $img = null;

    // Treatment
    private TreatmentType $type = TreatmentType::SPRAYING;
    private string $treatmentName = '';
    private ?float $amount = null;
    private ?string $unit = null;

    // common 
    private array $idRows = []; // task rows array
    
    // type of task 
    private bool $isDone = false;

    // Generic getters/setters
    
    /**
     * Check if the task is marked as done
     *
     * @return bool
     */
    public function getIsDone(): bool 
    {
        return $this->isDone;
    }
    
    /**
     * Mark the task as done
    *
    * @return void
    */
    public function markAsDone(): void {
        $this->isDone = true; 
    }
    
    /**
     * Get the type of the task
     *
     * @return TaskType
     */
    public function getType(): TaskType
    {
        return $this->taskType;
    }

    /**
     * Set the type of the task
     *
     * @param TaskType $type
     * @return void
     */
    public function setType(TaskType $type): void
    {
        $this->taskType = $type;
    }

    /**
     * Get the name of the task
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the task
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the note for the task
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Set the note for the task
     *
     * @param string $note
     * @return void
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Get the planned timestamp for the task
     *
     * @return DateTime|null
     */
    public function getPlannedTimestamp(): ?DateTime
    {
        return $this->plannedTimestamp;
    }

    /**
     * Set the planned timestamp for the task
     *
     * @param DateTime|null $plannedTimestamp
     * @return void
     */
    public function setPlannedTimestamp(?DateTime $plannedTimestamp): void
    {
        $this->plannedTimestamp = $plannedTimestamp;
    }

    /**
     * Get the IDs of workers assigned to the task
     *
     * @return array
     */
    public function getIdsOfWorkers(): array
    {
        return $this->idsOfWorkers;
    }

    /**
     * Set the IDs of workers assigned to the task
     *
     * @param array $idsOfWorkers
     * @return void
     */
    public function setIdsOfWorkers(array $idsOfWorkers): void
    {
        $this->idsOfWorkers = $idsOfWorkers;
    }

    // Harvest getters/setters

    /**
     * Get the ID of the vineyard
     *
     * @return int|null
     */
    public function getIdWineYard(): ?int
    {
        return $this->idWineYard;
    }

    /**
     * Set the ID of the vineyard
     *
     * @param int|null $idWineYard
     * @return void
     */
    public function setIdWineYard(?int $idWineYard): void
    {
        $this->idWineYard = $idWineYard;
    }

    /**
     * Get the ID of the wine variety
     *
     * @return int|null
     */
    public function getIdWineVariety(): ?int
    {
        return $this->idWineVariety;
    }

    /**
     * Set the ID of the wine variety
     *
     * @param int|null $idWineVariety
     * @return void
     */
    public function setIdWineVariety(?int $idWineVariety): void
    {
        $this->idWineVariety = $idWineVariety;
    }

    // Bottling getters/setters

    /**
     * Get the ID of the wine
     *
     * @return int|null
     */
    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    /**
     * Set the ID of the wine
     *
     * @param int|null $idWine
     * @return void
     */
    public function setIdWine(?int $idWine): void
    {
        $this->idWine = $idWine;
    }

    /**
     * Get the bottle volume
     *
     * @return float|null
     */
    public function getBottleVolume(): ?float
    {
        return $this->bottleVolume;
    }

    /**
     * Set the bottle volume
     *
     * @param float|null $bottleVolume
     * @return void
     */
    public function setBottleVolume(?float $bottleVolume): void
    {
        $this->bottleVolume = $bottleVolume;
    }

    /**
     * Get the image
     *
     * @return Image|null
     */
    public function getImg(): ?Image
    {
        return $this->img;
    }

    /**
     * Set the image
     *
     * @param Image|null $img
     * @return void
     */
    public function setImg(?Image $img): void
    {
        $this->img = $img;
    }

    // Treatment getters/setters

    /**
     * Get the IDs of rows
     *
     * @return array
     */
    public function getIdRows(): array
    {
        return $this->idRows;
    }

    /**
     * Set the IDs of rows
     *
     * @param array $idRows
     * @return void
     */
    public function setIdRows(array $idRows): void
    {
        $this->idRows = $idRows;
    }

    /**
     * Get the treatment type
     *
     * @return TreatmentType
     */
    public function getTreatmentType(): TreatmentType
    {
        return $this->type;
    }

    /**
     * Set the treatment type
     *
     * @param TreatmentType $type
     * @return void
     */
    public function setTreatmentType(TreatmentType $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the treatment name
     *
     * @return string
     */
    public function getTreatmentName(): string
    {
        return $this->treatmentName;
    }

    /**
     * Set the treatment name
     *
     * @param string $treatmentName
     * @return void
     */
    public function setTreatmentName(string $treatmentName): void
    {
        $this->treatmentName = $treatmentName;
    }

    /**
     * Get the amount
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Set the amount
     *
     * @param float|null $amount
     * @return void
     */
    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Get the unit
     *
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Set the unit
     *
     * @param string|null $unit
     * @return void
     */
    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }
}
