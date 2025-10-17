<?php

namespace App\Model\Order;

use App\Model\OrderItem\OrderItemRepository;
use App\Model\Order\CustomerOrder;
use Nette\Database\Explorer;

final class CustomerOrderRepository
{
    public function __construct(
        private Explorer $db,
        private OrderItemRepository $orderItemRepository
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
}
