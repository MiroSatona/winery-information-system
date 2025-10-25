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
    public function fetchItem(int $wineId, int $orderId): ?OrderItem
    {
        $row = $this->db->table('ORDER_ITEM')
            ->where('ID_ORDER', $orderId)
            ->where('ID_WINE', $wineId)
            ->fetch();

        return $row ? $this->createOrderItemFromRow($row) : null;
    }

    private function createOrderItemFromRow($row): OrderItem
    {
        $wine = $this->wineRepository->fetchWineById((int) $row->ID_WINE);

        return new OrderItem(
            idOrder: (int) $row->ID_ORDER,
            wine: $wine,
            bottlesCount: (int) $row->BOTTLES_COUNT,
            pricePerBottle: (float) $row->PRICE_PER_BOTTLE
        );
    }
    
    private function fetchItemRow(int $wineId, int $orderId) {
        return $this->db->table('ORDER_ITEM')
            ->where('ID_ORDER', $orderId)
            ->where('ID_WINE', $wineId)
            ->fetch();
    }
    
    public function deleteOrderItemById(int $wineId, int $orderId): bool {
        $row = $this->fetchItemRow($wineId, $orderId);
        if ($row == null) {
            return false;
        }
        
        $row->delete();
        return $this->wineRepository->updateWineFreeBottles($wineId, $row->BOTTLES_COUNT, true);
    }

    public function changeItemAmount(int $wineId, int $orderId, int $amount, bool $add): bool {
        $row = $this->fetchItemRow($wineId, $orderId);
        bdump($row);
        if ($row == null) {
            return false;
        }       

        if ($add) {
            $newAmount = $row->BOTTLES_COUNT + $amount;
        } else {
            $newAmount = $row->BOTTLES_COUNT - $amount;            
        }
        
        bdump($newAmount);
        if ($newAmount < 0) {
            return false;
        }

        if ($newAmount == 0) {
            return $this->deleteOrderItemById($wineId, $orderId);
        }

        if ($newAmount > $this->wineRepository->getWineAllBottleCount($wineId)) {
            bdump($this->wineRepository->getWineAllBottleCount($wineId));
            return false;
        }

        // transaction  
        $row->update([
            "BOTTLES_COUNT" => $newAmount
        ]);

        // end transaction
        return $this->wineRepository->updateWineFreeBottles($wineId, $amount, !$add);
    }

    public function addItem(int $orderId, int $wineId, int $amount): bool {
        // get all the items in the order 
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine == null) {
            return false;
        }
        
        // find out if an order allready exists, if so, just change the amount ... 
        $orderItem = $this->fetchItem($wineId, $orderId);
        if ($orderItem !== null) {
            return $this->changeItemAmount($wineId, $orderId, $amount, true);
        }
        $row = [
            "ID_ORDER" => $orderId,
            "ID_WINE" => $wineId,
            "BOTTLES_COUNT" => $amount,
            "PRICE_PER_BOTTLE" => $wine->currentPrice
        ];
        $this->db->table("ORDER_ITEM")->insert($row);
        return $this->wineRepository->updateWineFreeBottles($wineId, $amount, false);
    }    
} 
