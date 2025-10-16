<?php

namespace App\Model;

final class Wine
{
    public function __construct(
        public int $id,
        public ?int $idLiquidator,
        public ?int $idSellApprover,
        public ?int $idPicture,
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
        public string $productionNote
    ) {}
}