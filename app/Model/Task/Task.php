<?php

declare(strict_types=1);


namespace App\Model\Task;

use App\Model\Task\TaskState;
use Nette\Utils\DateTime;

class Task
{

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
        private ?int $idTaskConfirmer
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getState(): TaskState
    {
        return $this->state;
    }

    public function getCreationTimestamp(): DateTime
    {
        return $this->creationTimestamp;
    }

    public function getLastChangeTimestamp(): DateTime
    {
        return $this->lastChangeTimestamp;
    }

    public function getPlannedTimestamp(): ?DateTime
    {
        return $this->plannedTimestamp;
    }

    public function getExecutionTimestamp(): ?DateTime
    {
        return $this->executionTimestamp;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getIdTaskIssuer(): ?int
    {
        return $this->idTaskIssuer;
    }

    public function getIdTaskConfirmer(): ?int
    {
        return $this->idTaskConfirmer;
    }

    public function setState(TaskState $state): void
    {
        $this->state = $state;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCreationTimestamp(DateTime $timestamp): void
    {
        $this->creationTimestamp = $timestamp;
    }

    public function setLastChangeTimestamp(DateTime $timestamp): void
    {
        $this->lastChangeTimestamp = $timestamp;
    }

    public function setPlannedTimestamp(?DateTime $timestamp): void
    {
        $this->plannedTimestamp = $timestamp;
    }

    public function setExecutionTimestamp(?DateTime $timestamp): void
    {
        $this->executionTimestamp = $timestamp;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function setIdTaskIssuer(?int $idTaskIssuer): void
    {
        $this->idTaskIssuer = $idTaskIssuer;
    }

    public function setIdTaskConfirmer(?int $idTaskConfirmer): void
    {
        $this->idTaskConfirmer = $idTaskConfirmer;
    }
}