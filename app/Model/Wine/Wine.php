<?php

namespace App\Model\Wine;

use App\Model\Image\Image;

class Wine
{
    public string $typeCz;
    public string $colorCz;
    public string $gradeCz;
    public string $stateCz;

    private int $bottlesTotal;
    private float $bottleVolume;

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
        public array $variety,
        /** @var WineVariety[] $varieties */
        public array $varieties
    ) {
        $this->bottlesTotal = 0;
        $this->bottleVolume = 0;
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

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Undocumented function
     *
     * @return WineVariety[]
     */
    public function getVarieties(): array
    {
        return $this->varieties;
    }

    public function getVarietiesNames(): array
    {
        return $this->variety;
    }

    public function getImage(): ?Image
    {
        return $this->img;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getAlcoholPercentage(): int
    {
        return $this->alcoholPercentage;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function getVolume(): int
    {
        return $this->volume;
    }

    public function getProductionNote(): string
    {
        return $this->productionNote;
    }

    public function getSalesDescription(): ?string
    {
        return $this->salesDescription;
    }

    public function getBottlesLeft(): ?int
    {
        return $this->bottlesLeft;
    }

    public function getCurrentPrice(): ?int
    {
        return $this->currentPrice;
    }

    public function getBottlePrice(): ?int
    {
        return $this->bottlePrice;
    }

    public function getLeftSugar(): ?int
    {
        return $this->leftSugar;
    }

    public function getAcidContents(): ?int
    {
        return $this->acidContents;
    }

    public function getIdLiquidator(): ?int
    {
        return $this->idLiquidator;
    }

    public function getIdSellApprover(): ?int
    {
        return $this->idSellApprover;
    }

    public function getStateText(): string
    {
        return $this->fetchState($this->state);
    }

    public function getTypeText(): string
    {
        return $this->fetchType($this->type);
    }

    public function getColorText(): string
    {
        return $this->fetchColor($this->color);
    }

    public function getGradeText(): string
    {
        return $this->fetchGrade($this->grade);
    }


    public function getBottleVolume(): float
    {
        return $this->bottleVolume;
    }

    public function getBottlesTotal(): int
    {
        return $this->bottlesTotal;
    }

    public function setBottlesTotal(int $bottlesTotal): void
    {
        $this->bottlesTotal = $bottlesTotal;
    }

    public function setBottleVolume(float $bottleVolume): void
    {
        $this->bottleVolume = $bottleVolume;
    }


}
