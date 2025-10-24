<?php

declare(strict_types=1);


namespace App\Model\Task;

use App\Model\Task\TaskType;
use Nette\Utils\DateTime;
use App\Model\Image\Image;


class TaskData
{
    private string $name = '';
    private string $note = '';
    private ?DateTime $plannedTimestamp = null;
    private array $idsOfWorkers = [];
    private string $taskType = TaskType::TREATMENT->value; 

    // Harvest task
    private ?int $idWineYard = null;
    private ?int $idWineVariety = null;

    // Bottling
    private ?int $idWine = null;
    private ?int $bottleVolume = null;
    private ?Image $img = null;

    // Treatment
    private string $category = '';
    private string $treatmentName = '';
    private string $mnozstvi = '';
    private string $unit = '';

    // common 
    private array $idRows = [];

    public function __construct()
    {
    }

    // --- Getters & Setters ---
    
    public function getType(): string
    {
        return $this->taskType;
    }
    
    public function setType(string $type) 
    {
        $this->taskType = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getPlannedTimestamp(): ?DateTime
    {
        return $this->plannedTimestamp;
    }

    public function setPlannedTimestamp(?DateTime $plannedTimestamp): void
    {
        $this->plannedTimestamp = $plannedTimestamp;
    }

    public function getIdsOfWorkers(): array
    {
        return $this->idsOfWorkers;
    }

    public function setIdsOfWorkers(array $idsOfWorkers): void
    {
        $this->idsOfWorkers = $idsOfWorkers;
    }

    // Harvest getters/setters
    public function getIdWineYard(): ?int
    {
        return $this->idWineYard;
    }

    public function setIdWineYard(?int $idWineYard): void
    {
        $this->idWineYard = $idWineYard;
    }

    public function getIdWineVariety(): ?int
    {
        return $this->idWineVariety;
    }

    public function setIdWineVariety(?int $idWineVariety): void
    {
        $this->idWineVariety = $idWineVariety;
    }

    // Bottling getters/setters
    public function getIdWine(): ?int
    {
        return $this->idWine;
    }

    public function setIdWine(?int $idWine): void
    {
        $this->idWine = $idWine;
    }

    public function getBottleVolume(): ?int
    {
        return $this->bottleVolume;
    }

    public function setBottleVolume(?int $bottleVolume): void
    {
        $this->bottleVolume = $bottleVolume;
    }

    public function getImg(): ?Image
    {
        return $this->img;
    }

    public function setImg(?Image $img): void
    {
        $this->img = $img;
    }

    // Treatment getters/setters
    public function getIdRows(): array
    {
        return $this->idRows;
    }

    public function setIdRows(array $idRows): void
    {
        $this->idRows = $idRows;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getTreatmentName(): string
    {
        return $this->treatmentName;
    }

    public function setTreatmentName(string $treatmentName): void
    {
        $this->treatmentName = $treatmentName;
    }

    public function getMnozstvi(): string
    {
        return $this->mnozstvi;
    }

    public function setMnozstvi(string $mnozstvi): void
    {
        $this->mnozstvi = $mnozstvi;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

}