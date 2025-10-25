<?php

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItemRepository;
use App\Model\Orders\CustomerOrder;
use App\Model\Orders\OrderState;
use App\Model\User\UserRepository;
use Exception;
use Nette\Database\Explorer;

final class OrderRepository
{
    public function __construct(
        private Explorer $db,
        private OrderItemRepository $orderItemRepository,
        private UserRepository $usrRep
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
            $order->setUser($this->usrRep->getUserById($order->idCustomer));
        }

        return $order;
    }

    public function fetchAllOrders(bool $includeItems = false, string $searchTerm = ""): array
    {
        $like = '%' . $searchTerm . '%';
        $orders = $this->db->table('CUSTOMER_ORDER')
            ->where('CUSTOMER_ORDER.ID LIKE ? OR NAME LIKE ? OR ID_CUSTOMER.SURNAME LIKE ?', $like, $like, $like)
            ->fetchAll();

        $result = [];
        foreach ($orders as $row) {
            $order = $this->createOrderFromRow($row);

            if ($includeItems) {
                $order->items = $this->orderItemRepository->fetchItemsByOrderId($order->id);
            }
            
            $order->setUser($this->usrRep->getUserById($order->idCustomer));
            
            $result[] = $order;
            
        }
        return $result;
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
        $this->db->beginTransaction();
        try {
            $newRow = $this->db->table("CUSTOMER_ORDER")->insert($insertData);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

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
        $this->db->beginTransaction();
        try {
            $result = $this->orderItemRepository->deleteOrderItemById($wineId, $order->id);
            if ($result) {
                $this->updateOrderPrice($order->id);
                // if there is nothing in the order, delete the order 
                $order = $this->fetchCustomerOrder($userId, false);
                if ($order && $order->totalPrice == 0) {
                    $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $order->id)->fetch();
                    $row->delete();
                }
            } else {
                throw new Exception("failed to delete item order by id");
            }
            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function editAmountOrItemInOrder(int $userId, int $wineId, int $diff, bool $add): bool 
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $result = $this->orderItemRepository->changeItemAmount($wineId, $order->id, $diff, $add);
            if ($result) {
                $this->updateOrderPrice($order->id);
                $order = $this->fetchCustomerOrder($userId, false);
                if ($order && $order->totalPrice == 0) {
                    $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $order->id)->fetch();
                    $row->delete();
                }
            } else {
                throw new Exception("failed to edit amount of item in order");
            }
            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function addToCart(int $userId, int $wineId, int $amount): bool {
        $order = $this->fetchCustomerOrder($userId, true);
        if ($order == null) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $result = $this->orderItemRepository->addItem($order->id, $wineId, $amount);
            if ($result) {
                $this->updateOrderPrice($order->id);
            } else {
                throw new Exception("failed to add to card");
            }
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function updateOrderPrice(int $orderId, bool $doTransaction = false) {
        $order = $this->fetchOrderById($orderId, true);
        
        $totalPrice = 0;
        // calculate the full price of the order 
        foreach ($order->items as $item) {
            $totalPrice += $item->wine->currentPrice * $item->bottlesCount;
        }
        $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $orderId);
        if ($doTransaction) {
            $this->db->beginTransaction();
            try {
                $row->update([
                    "TOTAL_PRICE" => $totalPrice,
                ]);
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            return;
        }
        $row->update([
            "TOTAL_PRICE" => $totalPrice,
        ]);
    }

    public function updateDeliveryAdress(int $userId, string $type, ?string $addres): bool 
    {
        $this->db->beginTransaction();
        try {
            $order = $this->fetchCustomerOrder($userId);
            if (!$order) {
                $this->db->commit();
                return false;
            }

            // update the delivery type 
            $row = $this->db->table("CUSTOMER_ORDER")
            ->where("ID_CUSTOMER", $userId)
            ->where("ID", $order->id)
            ->fetch();
        
            if (!$row) {
                $this->db->commit();
                return false;
            }

            $row->update(
                ["DELIVERY_TYPE" => $type]
            );
            
            if (!$addres) {
                $row->update([
                    "DELIVERY_DATA" => ""
                ]);
                $this->db->commit();
                return false;
            }
            
            $row->update([
                "DELIVERY_DATA" => $addres
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
                
    public function clientpayfororder(int $userid): bool {
        $order = $this->fetchcustomerorder($userid);
        if (!$order) {
            return false;
        }
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userid)
        ->where("ID", $order->id)
        ->fetch(); 
        
        $this->db->beginTransaction();
        try {
            $row->update(
            [
                "STATE" => "PAID"
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function changeClientOrderStatus(int $orderId, string $newState): bool {
        $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $orderId)->fetch();
        if (!$row) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $row->update([
                "STATE" => $newState
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
