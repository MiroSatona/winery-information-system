<?php

namespace App\Model\Wine;

use App\Model\Image\Image;
use App\Model\Wine\WineColor;
use App\Model\Wine\WineType;
use App\Model\Wine\WineGrade;
use App\Model\Wine\WineState;

class Wine
{
    public string $typeCz;
    public string $colorCz;
    public string $gradeCz;
    public string $stateCz;

    private int $bottlesTotal;
    private float $bottleVolume;
      
    public function __construct(
        public ?int $id,
        public ?int $idLiquidator,
        public ?int $idSellApprover,
        public int $year,
        public float $alcoholPercentage,
        public WineType $type,
        public WineColor $color,
        public WineGrade $grade,
        public WineState $state,
        public int $volume,
        public ?float $bottlePrice,
        public ?float $currentPrice,
        public ?int $bottlesLeft,
        public ?float $leftSugar,
        public ?float $acidContents,
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

    private function fetchType(WineType $type): string
    {
        return match ($type) {
            WineType::DRY => 'Suché',
            WineType::HALF_DRY => 'Polosuché',
            WineType::HALF_SWEET => 'Polosladké',
            WineType::SWEET => 'Sladké',
            default => $type->value,
        };
    }

    private function fetchColor(WineColor $color): string
    {
        return match ($color) {
            WineColor::WHITE => 'Bílé',
            WineColor::RED => 'Červené',
            WineColor::ROSE => 'Růžové',
            default => $color->value,
        };
    }

    private function fetchGrade(WineGrade $grade): string
    {
        return match ($grade) {
            WineGrade::NONE => 'Bez přívlastku',
            WineGrade::CABINET => 'Kabinetní víno',
            WineGrade::LATE => 'Pozdní sběr',
            WineGrade::GRAPE_SELECTION => 'Výběr z hroznů',
            WineGrade::BERRY_SELECTION => 'Výběr z bobulí',
            WineGrade::ICE => 'Ledové víno',
            WineGrade::STRAW => 'Slámové víno',
            default => $grade->value,
        };
    }

    private function fetchState(WineState $state): string
    {
        return match ($state) {
            WineState::PRODUCTION => 'Ve výrobě',
            WineState::LIQUIDATED => 'Zlikvidováno',
            WineState::BOTTLED => 'Nalahvováno',
            WineState::SALE => 'V prodeji',
            WineState::SOLD => 'Prodáno',
            default => $state->value,
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getAlcoholPercentage(): float
    {
        return $this->alcoholPercentage;
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

    public function getState(): WineState
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

    public function getLeftSugar(): ?float
    {
        return $this->leftSugar;
    }

    public function getAcidContents(): ?float
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

    public function setImage(?Image $img): void
    {
        $this->img = $img;
    }
}
