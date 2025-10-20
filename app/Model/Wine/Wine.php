<?php

namespace App\Model\Wine;

use App\Model\Image\Image;

class Wine
{
    public string $typeCz;
    public string $colorCz;
    public string $gradeCz;
    public string $stateCz;

    public function __construct(
        public int $id,
        public ?int $idLiquidator,
        public ?int $idSellApprover,
        public int $year,
        public int $alcoholPercentage,
        public string $type,
        public string $color,
        public string $grade,
        public string $state,
        public int $volume,
        public ?int $bottlePrice,
        public ?int $currentPrice,
        public ?int $bottlesLeft,
        public ?int $leftSugar,
        public ?int $acidContents,
        public ?string $salesDescription,
        public string $productionNote,
        public string $name,
        public ?Image $img,
        public array $variety
    ) {
        // Populate Czech presentation values
        $this->typeCz  = $this->fetchType($this->type);
        $this->colorCz = $this->fetchColor($this->color);
        $this->gradeCz = $this->fetchGrade($this->grade);
        $this->stateCz = $this->fetchState($this->state);
    }

    private function fetchType(string $type): string
    {
        return match ($type) {
            'DRY' => 'Suché',
            'HALF_DRY' => 'Polosuché',
            'HALF_SWEET' => 'Polosladké',
            'SWEET' => 'Sladké',
            default => $type,
        };
    }

    private function fetchColor(string $color): string
    {
        return match ($color) {
            'WHITE' => 'Bílé',
            'RED' => 'Červené',
            'PINK' => 'Růžové',
            default => $color,
        };
    }

    private function fetchGrade(string $grade): string
    {
        return match ($grade) {
            'NONE' => 'Žádný',
            'CABINET' => 'Kabinetní víno',
            'LATE' => 'Pozdní sběr',
            'GRAPE_SELECTION' => 'Výběr z hroznů',
            'BERRY_SELECTION' => 'Výběr z bobulí',
            'ICE' => 'Ledové víno',
            'STRAW' => 'Slámové víno',
            default => $grade,
        };
    }

    private function fetchState(string $state): string
    {
        return match ($state) {
            'PRODUCTION' => 'Ve výrobě',
            'LIQUIDATED' => 'Zlikvidováno',
            'BOTTLED' => 'Nalahvováno',
            'SALE' => 'V prodeji',
            'SOLD' => 'Prodáno',
            default => $state,
        };
    }
}
