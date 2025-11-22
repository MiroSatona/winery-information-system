<?php

//FILE:     WineRow.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a wine row in a vineyard.

declare(strict_types=1);

namespace App\Model\WineRow;

use App\Model\WineVariety\WineVariety;

/**
 * Represents a single Wine Row in a vineyard
 */
final class WineRow
{
    // associated WineVariety object, set separately
    private WineVariety $wineVarietyType;

    /**
     * Wine Row constructor
     *
     * @param int $id
     * @param bool $active
     * @param int $vineyardId
     * @param int $wineVarietyId
     * @param int $headCount
     * @param int $yearPlanted
     * @param bool $inConflict
     */
    public function __construct(
        private int $id,
        private bool $active,
        private int $vineyardId,
        private int $wineVarietyId,
        private int $headCount,
        private int $yearPlanted,
        private bool $inConflict = false
    ) {
    }

    /**
     * Mark this wine row as being in conflict (harvest - treatment)
     *
     * @return void
     */
    public function setInConflict(): void
    {
        $this->inConflict = true;
    }

    /**
     * Check if this wine row is in conflict (harvest - treatment)
     *
     * @return bool
     */
    public function isInConflict(): bool
    {
        return $this->inConflict;
    }

    /**
     * Get the ID of the wine row
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the vineyard ID this row belongs to
     *
     * @return int
     */
    public function getVineyardId(): int
    {
        return $this->vineyardId;
    }

    /**
     * Check if this wine row is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get the wine variety ID for this row
     *
     * @return int
     */
    public function getWineVarietyId(): int
    {
        return $this->wineVarietyId;
    }

    /**
     * Get the number of heads (wine plants) on this row
     *
     * @return int
     */
    public function getHeadCount(): int
    {
        return $this->headCount;
    }

    /**
     * Get the year this row was planted
     *
     * @return int
     */
    public function getYearPlanted(): int
    {
        return $this->yearPlanted;
    }

    /**
     * Get the WineVariety object for this row
     *
     * @return WineVariety
     */
    public function getWineVariety(): WineVariety
    {
        return $this->wineVarietyType;
    }

    /**
     * Set the WineVariety object for this row
     *
     * @param WineVariety $wineVarietyType
     * @return void
     */
    public function setWineVariety(WineVariety $wineVarietyType): void
    {
        $this->wineVarietyType = $wineVarietyType;
    }
}
