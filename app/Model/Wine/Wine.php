<?php
//FILE:     Wine.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of wine entity

namespace App\Model\Wine;

use App\Model\Image\Image;
use App\Model\Wine\WineColor;
use App\Model\Wine\WineType;
use App\Model\Wine\WineGrade;
use App\Model\Wine\WineState;

/**
 * Represents a wine entity (bottle or batch) in the system
 */
class Wine
{
    private int $bottlesTotal;
    private float $bottleVolume;
      
    /**
     * Wine constructor
     *
     * @param int|null $id
     * @param int|null $idLiquidator
     * @param int|null $idSellApprover
     * @param int $year
     * @param float $alcoholPercentage
     * @param WineType $type
     * @param WineColor $color
     * @param WineGrade $grade
     * @param WineState $state
     * @param int $volume
     * @param float|null $bottlePrice
     * @param float|null $currentPrice
     * @param int|null $bottlesLeft
     * @param float|null $leftSugar
     * @param float|null $acidContents
     * @param string|null $salesDescription
     * @param string $productionNote
     * @param string $name
     * @param Image|null $img
     * @param array $variety
     * @param WineVariety[] $varieties
     */
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
    }

    /**
     * Translate WineType enum to Czech string
     *
     * @param WineType $type
     * @return string
     */
    private function translateType(WineType $type): string
    {
        return match ($type) {
            WineType::DRY => 'Suché',
            WineType::HALF_DRY => 'Polosuché',
            WineType::HALF_SWEET => 'Polosladké',
            WineType::SWEET => 'Sladké',
            default => $type->value,
        };
    }

    /**
     * Translate WineColor enum to Czech string
     *
     * @param WineColor $color
     * @return string
     */
    private function translateColor(WineColor $color): string
    {
        return match ($color) {
            WineColor::WHITE => 'Bílé',
            WineColor::RED => 'Červené',
            WineColor::ROSE => 'Růžové',
            default => $color->value,
        };
    }

    /**
     * Translate WineGrade enum to Czech string
     *
     * @param WineGrade $grade
     * @return string
     */
    private function translateGrade(WineGrade $grade): string
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

    /**
     * Translate WineState enum to Czech string
     *
     * @param WineState $state
     * @return string
     */
    private function translateState(WineState $state): string
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

    /**
     * Get the name of the wine
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the wine varieties (objects)
     *
     * @return WineVariety[]
     */
    public function getVarieties(): array
    {
        return $this->varieties;
    }

    /**
     * Get the names of the wine varieties
     *
     * @return array
     */
    public function getVarietiesNames(): array
    {
        return $this->variety;
    }

    /**
     * Get the image of the wine
     *
     * @return Image|null
     */
    public function getImage(): ?Image
    {
        return $this->img;
    }

    /**
     * Get the ID of the wine
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the year of the wine
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get the alcohol percentage of the wine
     *
     * @return float
     */
    public function getAlcoholPercentage(): float
    {
        return $this->alcoholPercentage;
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
     * Get the wine state
     *
     * @return WineState
     */
    public function getState(): WineState
    {
        return $this->state;
    }

    /**
     * Get the wine volume
     *
     * @return int
     */
    public function getVolume(): int
    {
        return $this->volume;
    }

    /**
     * Get the production note for the wine
     *
     * @return string
     */
    public function getProductionNote(): string
    {
        return $this->productionNote;
    }

    /**
     * Get the sales description for the wine
     *
     * @return string|null
     */
    public function getSalesDescription(): ?string
    {
        return $this->salesDescription;
    }

    /**
     * Get the number of bottles left
     *
     * @return int|null
     */
    public function getBottlesLeft(): ?int
    {
        return $this->bottlesLeft;
    }

    /**
     * Get the current price of the wine
     *
     * @return int|null
     */
    public function getCurrentPrice(): ?int
    {
        return $this->currentPrice;
    }

    /**
     * Get the bottle price of the wine
     *
     * @return int|null
     */
    public function getBottlePrice(): ?int
    {
        return $this->bottlePrice;
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
     * Get the ID of the liquidator
     *
     * @return int|null
     */
    public function getIdLiquidator(): ?int
    {
        return $this->idLiquidator;
    }

    /**
     * Get the ID of the sell approver
     *
     * @return int|null
     */
    public function getIdSellApprover(): ?int
    {
        return $this->idSellApprover;
    }

    /**
     * Get the translated state text
     *
     * @return string
     */
    public function getStateText(): string
    {
        return $this->translateState($this->state);
    }

    /**
     * Get the translated type text
     *
     * @return string
     */
    public function getTypeText(): string
    {
        return $this->translateType($this->type);
    }

    /**
     * Get the translated color text
     *
     * @return string
     */
    public function getColorText(): string
    {
        return $this->translateColor($this->color);
    }

    /**
     * Get the translated grade text
     *
     * @return string
     */
    public function getGradeText(): string
    {
        return $this->translateGrade($this->grade);
    }


    /**
     * Get the bottle volume
     *
     * @return float
     */
    public function getBottleVolume(): float
    {
        return $this->bottleVolume;
    }

    /**
     * Get the total number of bottles
     *
     * @return int
     */
    public function getBottlesTotal(): int
    {
        return $this->bottlesTotal;
    }

    /**
     * Set the total number of bottles
     *
     * @param int $bottlesTotal
     * @return void
     */
    public function setBottlesTotal(int $bottlesTotal): void
    {
        $this->bottlesTotal = $bottlesTotal;
    }

    /**
     * Set the bottle volume
     *
     * @param float $bottleVolume
     * @return void
     */
    public function setBottleVolume(float $bottleVolume): void
    {
        $this->bottleVolume = $bottleVolume;
    }

    /**
     * Set the image for the wine
     *
     * @param Image|null $img
     * @return void
     */
    public function setImage(?Image $img): void
    {
        $this->img = $img;
    }
}
