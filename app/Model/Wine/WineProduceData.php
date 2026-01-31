<?php

//FILE:     WineProduceData.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of data needed for handling wine production

declare(strict_types=1);

namespace App\Model\Wine;

use App\Model\Wine\WineColor;
use App\Model\Wine\WineGrade;
use App\Model\Wine\WineType;

/**
 * Object encapsulating data needed for wine production
 *
 * Used to store form data of wine production (transitions between form steps)
 */
final class WineProduceData
{
    /**
     * WineProduceData constructor
     *
     * @param string|null $name
     * @param int|null $year
     * @param WineType $type
     * @param WineColor $color
     * @param WineGrade $grade
     * @param int|null $volume
     * @param float|null $alcoholPercentage
     * @param float|null $leftSugar
     * @param float|null $acidContents
     * @param string|null $productionNote
     */
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

    /**
     * Get the name of the wine
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Get the year of the wine
     *
     * @return int|null
     */
    public function getYear(): ?int
    {
        return $this->year;
    }

    /**
     * Get the wine type
     *
     * @return WineType
     */
    public function getType(): WineType
    {
        return $this->type;
    }

    /**
     * Get the wine color
     *
     * @return WineColor
     */
    public function getColor(): WineColor
    {
        return $this->color;
    }

    /**
     * Get the wine grade
     *
     * @return WineGrade
     */
    public function getGrade(): WineGrade
    {
        return $this->grade;
    }

    /**
     * Get the wine volume
     *
     * @return int|null
     */
    public function getVolume(): ?int
    {
        return $this->volume;
    }

    /**
     * Get the alcohol percentage of the wine
     *
     * @return float|null
     */
    public function getAlcoholPercentage(): ?float
    {
        return $this->alcoholPercentage;
    }

    /**
     * Get the left sugar content of the wine
     *
     * @return float|null
     */
    public function getLeftSugar(): ?float
    {
        return $this->leftSugar;
    }

    /**
     * Get the acid contents of the wine
     *
     * @return float|null
     */
    public function getAcidContents(): ?float
    {
        return $this->acidContents;
    }

    /**
     * Get the production note for the wine
     *
     * @return string|null
     */
    public function getProductionNote(): ?string
    {
        return $this->productionNote;
    }
}
