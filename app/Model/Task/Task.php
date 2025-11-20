<?php
//FILE:     Task.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Base class for all task types. Internal representation.

declare(strict_types=1);

namespace App\Model\Task;

use App\Model\Task\TaskState;
use Nette\Utils\DateTime;

/**
 * Represents a basic Task entity, that is extended by Treatment, Harvest and Bottling.
 */
class Task
{
    /**
     * Task constructor
     *
     * @param int $id
     * @param string $name
     * @param TaskState $state
     * @param DateTime $creationTimestamp
     * @param DateTime $lastChangeTimestamp
     * @param DateTime|null $plannedTimestamp
     * @param DateTime|null $executionTimestamp
     * @param string|null $note
     * @param int|null $idTaskIssuer
     * @param int|null $idTaskConfirmer
     * @param bool $inConflict
     */
    public function __construct(
        private int $id,
        private string $name,
        private TaskState $state,
        private DateTime $creationTimestamp,
        private DateTime $lastChangeTimestamp,
        private ?DateTime $plannedTimestamp,
        private ?DateTime $executionTimestamp,
        private ?string $note,
        private ?int $idTaskIssuer,
        private ?int $idTaskConfirmer,
        private bool $inConflict = false
    ) {
    }
    
    /**
     * Check if the task is in conflict
     *
     * @return bool
     */
    public function isInConflict(): bool 
    {
        return $this->inConflict;
    }
    
    /**
     * Set the task as in conflict
     */
    public function setInConflict() {
        $this->inConflict = true;
    }

    /**
     * Get the ID of the task
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * Get the state of the task
     *
     * @return TaskState
     */
    public function getState(): TaskState
    {
        return $this->state;
    }

    /**
     * Get the creation timestamp of the task
     *
     * @return DateTime
     */
    public function getCreationTimestamp(): DateTime
    {
        return $this->creationTimestamp;
    }

    /**
     * Get the last change timestamp of the task
     *
     * @return DateTime
     */
    public function getLastChangeTimestamp(): DateTime
    {
        return $this->lastChangeTimestamp;
    }

    /**
     * Get the planned timestamp of the task
     *
     * @return DateTime|null
     */
    public function getPlannedTimestamp(): ?DateTime
    {
        return $this->plannedTimestamp;
    }

    /**
     * Get the execution timestamp of the task
     *
     * @return DateTime|null
     */
    public function getExecutionTimestamp(): ?DateTime
    {
        return $this->executionTimestamp;
    }

    /**
     * Get the note for the task
     *
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Get the ID of the task issuer
     *
     * @return int|null
     */
    public function getIdTaskIssuer(): ?int
    {
        return $this->idTaskIssuer;
    }

    /**
     * Get the ID of the task confirmer
     *
     * @return int|null
     */
    public function getIdTaskConfirmer(): ?int
    {
        return $this->idTaskConfirmer;
    }

    /**
     * Set the state of the task
     *
     * @param TaskState $state
     * @return void
     */
    public function setState(TaskState $state): void
    {
        $this->state = $state;
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
     * Set the creation timestamp of the task
     *
     * @param DateTime $timestamp
     * @return void
     */
    public function setCreationTimestamp(DateTime $timestamp): void
    {
        $this->creationTimestamp = $timestamp;
    }

    /**
     * Set the last change timestamp of the task
     *
     * @param DateTime $timestamp
     * @return void
     */
    public function setLastChangeTimestamp(DateTime $timestamp): void
    {
        $this->lastChangeTimestamp = $timestamp;
    }

    /**
     * Set the planned timestamp of the task
     *
     * @param DateTime|null $timestamp
     * @return void
     */
    public function setPlannedTimestamp(?DateTime $timestamp): void
    {
        $this->plannedTimestamp = $timestamp;
    }

    /**
     * Set the execution timestamp of the task
     *
     * @param DateTime|null $timestamp
     * @return void
     */
    public function setExecutionTimestamp(?DateTime $timestamp): void
    {
        $this->executionTimestamp = $timestamp;
    }

    /**
     * Set the note for the task
     *
     * @param string|null $note
     * @return void
     */
    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /**
     * Set the ID of the task issuer
     *
     * @param int|null $idTaskIssuer
     * @return void
     */
    public function setIdTaskIssuer(?int $idTaskIssuer): void
    {
        $this->idTaskIssuer = $idTaskIssuer;
    }

    /**
     * Set the ID of the task confirmer
     *
     * @param int|null $idTaskConfirmer
     * @return void
     */
    public function setIdTaskConfirmer(?int $idTaskConfirmer): void
    {
        $this->idTaskConfirmer = $idTaskConfirmer;
    }
}
