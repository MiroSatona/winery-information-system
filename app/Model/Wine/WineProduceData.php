<?php

declare(strict_types=1);

namespace App\Model\Wine;

final class WineProduceData
{
    public function __construct(
        private ?string $name,
        private ?int $year,
        private string $type,
        private string $color,
        private string $grade,
        private ?int $volume,
        private ?float $alcoholPercentage,
        private ?float $leftSugar,
        private ?float $acidContents,
        private ?string $productionNote
    ) {
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function getVolume(): ?int
    {
        return $this->volume;
    }

    public function getAlcoholPercentage(): ?float
    {
        return $this->alcoholPercentage;
    }

    public function getLeftSugar(): ?float
    {
        return $this->leftSugar;
    }

    public function getAcidContents(): ?float
    {
        return $this->acidContents;
    }

    public function getProductionNote(): ?string
    {
        return $this->productionNote;
    }
}