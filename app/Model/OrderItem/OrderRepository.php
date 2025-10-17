<?php

namespace App\Model\OrderItem;

use App\Model\Wine\WineRepository;
use App\Model\OrderItem\OrderItem;
use Nette\Database\Explorer;

final class OrderItemRepository
{
    public function __construct(
        private Explorer $db,
        private WineRepository $wineRepository
    ) {}

    /**
     * Fetch all items belonging to a specific order
     */
    public function fetchItemsByOrderId(int $orderId): array
    {
        $rows = $this->db->table('ORDER_ITEM')->where('ID_ORDER', $orderId);
        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->createOrderItemFromRow($row);
        }

        return $items;
    }

    /**
     * Fetch one item by order ID and item ID (composite key)
     */
    public function fetchItem(int $orderId, int $itemId): ?OrderItem
    {
        $row = $this->db->table('ORDER_ITEM')
            ->where('ID_ORDER', $orderId)
            ->where('ID_ITEM', $itemId)
            ->fetch();

        return $row ? $this->createOrderItemFromRow($row) : null;
    }

    private function createOrderItemFromRow($row): OrderItem
    {
        $wine = $this->wineRepository->fetchWineById((int) $row->ID_WINE);

        return new OrderItem(
            idOrder: (int) $row->ID_ORDER,
            idItem: (int) $row->ID_ITEM,
            wine: $wine,
            bottlesCount: (int) $row->BOTTLES_COUNT,
            pricePerBottle: (float) $row->PRICE_PER_BOTTLE
        );
    }
}
