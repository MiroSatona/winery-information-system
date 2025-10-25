<?php

declare(strict_types=1);

namespace App\Model\Wine;

use App\Model\Wine\WineColor;
use App\Model\Wine\WineGrade;
use App\Model\Wine\WineType;

final class WineProduceData
{
    public function __construct(
        private ?string $name,
        private ?int $year,
        private WineType $type,
        private WineColor $color,
        private WineGrade $grade,
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

    public function getType(): WineType
    {
        return $this->type;
    }

    public function getColor(): WineColor
    {
        return $this->color;
    }

    public function getGrade(): WineGrade
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