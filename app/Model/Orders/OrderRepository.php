<?php

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItemRepository;
use App\Model\Orders\CustomerOrder;
use App\Model\Orders\OrderState;
use Nette\Database\Explorer;

final class OrderRepository
{
    public function __construct(
        private Explorer $db,
        private OrderItemRepository $orderItemRepository,
    ) {}

    public function fetchOrderById(int $orderId, bool $includeItems = false): ?CustomerOrder
    {
        $row = $this->db->table('CUSTOMER_ORDER')->where('ID', $orderId)->fetch();
        if (!$row) {
            return null;
        }

        $order = $this->createOrderFromRow($row);

        if ($includeItems) {
            $order->items = $this->orderItemRepository->fetchItemsByOrderId($order->id);
        }

        return $order;
    }

    public function fetchAllOrders(bool $includeItems = false): array
    {
        $rows = $this->db->table('CUSTOMER_ORDER');
        $orders = [];

        foreach ($rows as $row) {
            $order = $this->createOrderFromRow($row);

            if ($includeItems) {
                $order->items = $this->orderItemRepository->fetchItemsByOrderId($order->id);
            }

            $orders[] = $order;
        }

        return $orders;
    }
    
    public function fetchCustomerOrders(int $userId) 
    {
        $rows = $this->db->table("CUSTOMER_ORDER")->where("ID_CUSTOMER", $userId);
        
        $returnItems = [];
        foreach($rows as $row) {
            $order = $this->createOrderFromRow($row);
            $order->items = $this->orderItemRepository->fetchItemsByOrderId($order->id);
            $returnItems[] = $order;
        }
        return $returnItems;
    }

    private function createOrderFromRow($row): CustomerOrder
    {
        if ($row == null) {
            return $row; 
        }
        return new CustomerOrder(
            id: (int) $row->ID,
            idCustomer: $row->ID_CUSTOMER !== null ? (int) $row->ID_CUSTOMER : null,
            totalPrice: (float) $row->TOTAL_PRICE,
            state: (string) $row->STATE,
            deliveryType: $row->DELIVERY_TYPE !== null ? (string) $row->DELIVERY_TYPE : null,
            orderTimestamp: (string) $row->ORDER_TIMESTAMP,
            deliveryData: (string) $row->DELIVERY_DATA,
        );
    }
    
    public function fetchCustomerOrder(int $userId, bool $createIfNotfound = false) {
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userId)
        ->where("STATE", OrderState::reserved)
        ->fetch();
   
        if ($row !== null) {
            return $this->createOrderFromRow($row);
        }
        
        if (!$createIfNotfound) {
            return null;
        }
       
        // No existing order found -> create one
        $insertData = [
            "ID_CUSTOMER" => $userId,
            "TOTAL_PRICE" => 0.00,
            "STATE" => OrderState::reserved,
            "DELIVERY_TYPE" => "PICKUP",      // Required (no default)
            "DELIVERY_DATA" => "",
        ];

        $newRow = $this->db->table("CUSTOMER_ORDER")->insert($insertData);

        if (!$newRow) {
            return null; // insert failed for some reason
        }

        return $this->createOrderFromRow($newRow);
    }

    public function removeItemFromOrder(int $userId, int $wineId): bool
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        
        $result = $this->orderItemRepository->deleteOrderItemById($wineId, $order->id);
        if ($result) {
            $this->updateOrderPrice($order->id);
            // if there is nothing in the order, delete the order 
            $order = $this->fetchCustomerOrder($userId, false);
            if ($order && $order->totalPrice == 0) {
                $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $order->id)->fetch();
                $row->delete();
            }
        }
        return $result;
    }

    public function editAmountOrItemInOrder(int $userId, int $wineId, int $diff, bool $add): bool 
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        
        $result = $this->orderItemRepository->changeItemAmount($wineId, $order->id, $diff, $add);
        if ($result) {
            $this->updateOrderPrice($order->id);
            $order = $this->fetchCustomerOrder($userId, false);
            if ($order && $order->totalPrice == 0) {
                $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $order->id)->fetch();
                $row->delete();
            }
        }
        return $result;
    }
    
    public function addToCart(int $userId, int $wineId, int $amount): bool {
        $order = $this->fetchCustomerOrder($userId, true);
        if ($order == null) {
            return false;
        }
        $result = $this->orderItemRepository->addItem($order->id, $wineId, $amount);
        if ($result) {
            $this->updateOrderPrice($order->id);
        }
        return $result;
    }
    
    public function updateOrderPrice(int $orderId) {
        $order = $this->fetchOrderById($orderId, true);
        
        $totalPrice = 0;
        // calculate the full price of the order 
        foreach ($order->items as $item) {
            $totalPrice += $item->wine->currentPrice * $item->bottlesCount;
        }
        $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $orderId);
        $row->update([
            "TOTAL_PRICE" => $totalPrice,
        ]);
    }

    public function updateDeliveryAdress(int $userId, string $type, ?string $addres): bool 
    {
        $order = $this->fetchCustomerOrder($userId);
        if (!$order) {
            return false;
        }

        // update the delivery type 
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userId)
        ->where("ID", $order->id)
        ->fetch();
    
        if (!$row) {
            return false;
        }

        $row->update(
            ["DELIVERY_TYPE" => $type]
        );
        
        if (!$addres) {
            $row->update([
                "DELIVERY_DATA" => ""
            ]);
            return true;
        }
        
        $row->update([
            "DELIVERY_DATA" => $addres
        ]);
        return true;
    }
    
    public function clientPayForOrder(int $userId): bool {
        $order = $this->fetchCustomerOrder($userId);
        if (!$order) {
            return false;
        }
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userId)
        ->where("ID", $order->id)
        ->fetch(); 
        
        $row->update(
            [
                "STATE" => "PAID"
            ]
            );
        return true;
    }
}
