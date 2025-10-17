<?php

namespace App\Model\Wine;

use App\Model\Image\ImageRepository;
use App\Model\Wine\Wine;
use Nette\Database\Explorer;

final class WineRepository
{
    public function __construct(
        private Explorer $db,
        private ImageRepository $imageRepository
    ) {}

    public function fetchWineById(int $wineId): ?Wine
    {
        $wineRow = $this->db->table('WINE')->where('ID', $wineId)->fetch();
        return $wineRow ? $this->createWineFromRow($wineRow) : null;
    }

    public function fetchAllWines(): array
    {
        $rows = $this->db->table('WINE');
        $wines = [];

        foreach ($rows as $row) {
            $wines[] = $this->createWineFromRow($row);
        }

        return $wines;
    }

    private function createWineFromRow($row): Wine
    {
        // Fetch associated image (via foreign key ID_PICTURE)
        $image = null;
        if ($row->ID_PICTURE !== null) {
            $image = $this->imageRepository->fetchImageById((int) $row->ID_PICTURE);
        }

        return new Wine(
            id: (int) $row->ID,
            idLiquidator: $row->ID_LIQUIDATOR !== null ? (int) $row->ID_LIQUIDATOR : null,
            idSellApprover: $row->ID_SELL_APPROVER !== null ? (int) $row->ID_SELL_APPROVER : null,
            year: (int) $row->YEAR,
            alcoholPercentage: (int) $row->ALCOHOL_PERCENTAGE,
            type: (string) $row->TYPE,
            color: (string) $row->COLOR,
            grade: (string) $row->GRADE,
            state: (string) $row->STATE,
            volume: (int) $row->VOLUME,
            bottlePrice: $row->BOTTLE_PRICE !== null ? (int) $row->BOTTLE_PRICE : null,
            currentPrice: $row->CURRENT_PRICE !== null ? (int) $row->CURRENT_PRICE : null,
            bottlesLeft: $row->BOTTLES_LEFT !== null ? (int) $row->BOTTLES_LEFT : null,
            leftSugar: $row->LEFT_SUGAR !== null ? (int) $row->LEFT_SUGAR : null,
            acidContents: $row->ACID_CONTENTS !== null ? (int) $row->ACID_CONTENTS : null,
            salesDescription: $row->SALES_DESCRIPTION !== null ? (string) $row->SALES_DESCRIPTION : null,
            productionNote: (string) $row->PRODUCTION_NOTE,
            name: (string) $row->WINE_NAME,
            img: $image
        );
    }
}
