<?php

declare(strict_types=1);

namespace App\Model\WineRow;

use App\Model\WineVariety\WineVariety;

final class WineRow
{
    private WineVariety $wineVarietyType;

    public function __construct(
        private int $id,
        private bool $active,
        private int $vineyardId,
        private int $wineVarietyId,
        private int $headCount,
        private int $yearPlanted
    ) {
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getVineyardId(): int
    {
        return $this->vineyardId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getWineVarietyId(): int
    {
        return $this->wineVarietyId;
    }

    public function getHeadCount(): int
    {
        return $this->headCount;
    }

    public function getYearPlanted(): int
    {
        return $this->yearPlanted;
    }

    public function getWineVariety(): WineVariety
    {
        return $this->wineVarietyType;
    }

    public function setWineVariety(WineVariety $wineVarietyType): void
    {
        $this->wineVarietyType = $wineVarietyType;
    }
}