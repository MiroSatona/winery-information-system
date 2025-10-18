<?php

declare(strict_types=1);

namespace App\Model\WineRow;


final class WineRow
{
    public function __construct(
        private int $id,
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
}