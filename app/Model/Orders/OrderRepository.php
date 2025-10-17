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
            billingInfo: (string) $row->BILLING_INFO
        );
    }
    
    public function fetchCustomerOrder(int $userId) {
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userId)
        ->where("STATE", OrderState::reserved)
        ->fetch();
   
        if ($row !== null) {
            return $this->createOrderFromRow($row);
        }
        
       
        // No existing order found -> create one
        $insertData = [
            "ID_CUSTOMER" => $userId,
            "TOTAL_PRICE" => 0.00,
            "STATE" => OrderState::reserved,
            "DELIVERY_TYPE" => "PICKUP",      // Required (no default)
            "DELIVERY_DATA" => "",
            "BILLING_INFO" => "",
        ];

        $newRow = $this->db->table("CUSTOMER_ORDER")->insert($insertData);

        if (!$newRow) {
            return null; // insert failed for some reason
        }

        return $this->createOrderFromRow($newRow);
    }

    public function removeItemFromOrder(int $userId, int $itemId): bool
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        
        return $this->orderItemRepository->deleteOrderItemById($itemId, $order->id);
    }

    public function editAmountOrItemInOrder(int $userId, int $itemId, int $newAmount): bool 
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        
        return $this->orderItemRepository->changeItemAmount($itemId, $order->id, $newAmount);
    }
    
    public function addToCart(int $userId, int $wineId, int $amount): bool {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        return $this->orderItemRepository->addItem($order->id, $wineId, $amount);
    }
}
