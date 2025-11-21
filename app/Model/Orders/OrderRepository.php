<?php
//FILE:     OrderRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for handling customer orders in the database.

namespace App\Model\Orders;

use App\Model\OrderItem\OrderItemRepository;
use App\Model\Orders\CustomerOrder;
use App\Model\Orders\OrderState;
use App\Model\User\UserRepository;
use Exception;
use Nette\Database\Explorer;

/**
 * Handles customer orders, including fetching, creating, and updating orders in the database.
 */
final class OrderRepository
{
    /**
     * OrderRepository constructor
     *
     * @param Explorer $db
     * @param OrderItemRepository $orderItemRepository
     * @param UserRepository $usrRep
     */
    public function __construct(
        private Explorer $db,
        private OrderItemRepository $orderItemRepository,
        private UserRepository $usrRep
    ) {}
    
    /**
     * Fetch a single order by its ID
     *
     * @param int $orderId
     * @param bool $includeItems Whether to include associated order items
     * @return CustomerOrder|null
     */
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

    /**
     * Fetch all orders, optionally including items and filtering by a search term
     *
     * @param bool $includeItems Whether to include associated order items
     * @param string $searchTerm Search term to filter orders
     * @return CustomerOrder[]
     */
    public function fetchAllOrders(bool $includeItems = false, string $searchTerm = ""): array
    {
        $like = '%' . $searchTerm . '%';
        $orders = $this->db->table('CUSTOMER_ORDER') // fetch filtered orders
            ->where('CUSTOMER_ORDER.ID LIKE ? OR NAME LIKE ? OR ID_CUSTOMER.SURNAME LIKE ?', $like, $like, $like)
            ->fetchAll();

        $result = [];
        foreach ($orders as $row) { // create CustomerOrder objects one by one
            $order = $this->createOrderFromRow($row);

            if ($includeItems) {
                $order->items = $this->orderItemRepository->fetchItemsByOrderId($order->id);
            }
            
            $order->setUser($this->usrRep->getUserById($order->idCustomer));
            
            $result[] = $order;
            
        }
        return $result;
    }
    
    /**
     * Fetch all orders for a specific customer
     *
     * @param int $userId
     * @return CustomerOrder[]
     */
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

    /**
     * Create a CustomerOrder object from a database row
     *
     * @param mixed $row
     * @return CustomerOrder
     */
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
            orderTimestamp: $row->ORDER_TIMESTAMP->format('d.m.Y H:i:s'),
            deliveryData: (string) $row->DELIVERY_DATA,
        );
    }
    
    /**
     * Fetch the current reserved order for a customer, creating one if it doesn't exist
     *
     * @param int $userId
     * @param bool $createIfNotfound Whether to create a new order if none is found
     * @return CustomerOrder|null
     */
    public function fetchCustomerOrder(int $userId, bool $createIfNotfound = false) {
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userId)
        ->where("STATE", OrderState::RESERVED)
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
            "STATE" => OrderState::RESERVED,
            "DELIVERY_TYPE" => "PICKUP",      
            "DELIVERY_DATA" => "",
        ];
        try {
            $this->db->beginTransaction();
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

    /**
     * Remove an item from a customer's order
     *
     * @param int $userId
     * @param int $wineId
     * @return bool True on success, false on failure
     */
    public function removeItemFromOrder(int $userId, int $wineId): bool
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        try {
            $this->db->beginTransaction();
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

    /**
     * Edit the amount of an item in a customer's order, or add/remove the item
     *
     * @param int $userId
     * @param int $wineId
     * @param int $diff
     * @param bool $add
     * @return bool True on success, false on failure
     */
    public function editAmountOrItemInOrder(int $userId, int $wineId, int $diff, bool $add): bool 
    {
        $order = $this->fetchCustomerOrder($userId);
        if ($order == null) {
            return false;
        }
        try {
            $this->db->beginTransaction();
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
    
    /**
     * Add an item to a customer's order
     *
     * @param int $userId
     * @param int $wineId
     * @param int $amount
     * @return bool True on success, false on failure
     */
    public function addToCart(int $userId, int $wineId, int $amount): bool {
        $order = $this->fetchCustomerOrder($userId, true);
        if ($order == null) {
            return false;
        }
        try {
            $this->db->beginTransaction();
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
    
    /**
     * Update the total price of an order based on its items
     *
     * @param int $orderId
     * @param bool $doTransaction Whether to wrap the update in a transaction
     */
    public function updateOrderPrice(int $orderId, bool $doTransaction = false) {
        $order = $this->fetchOrderById($orderId, true);
        
        $totalPrice = 0;
        // calculate the full price of the order 
        foreach ($order->items as $item) {
            $totalPrice += $item->wine->currentPrice * $item->bottlesCount;
        }
        $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $orderId);
        if ($doTransaction) {
            try {
                $this->db->beginTransaction();
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

    /**
     * Update the Delivery address of an order 
     *
     * @param integer $userId
     * @param string $type
     * @param string|null $address
     * @return boolean
     */
    public function updateDeliveryAddress(int $userId, string $type, ?string $address): bool 
    {
        try {
            $this->db->beginTransaction();
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
            
            if (!$address) {
                $row->update([
                    "DELIVERY_DATA" => ""
                ]);
                $this->db->commit();
                return false;
            }
            
            $row->update([
                "DELIVERY_DATA" => $address
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
               
    /**
     * Mark the payment of an order 
     *
     * @param integer $userid
     * @return boolean
     */
    public function clientPayForOrder(int $userid): bool {
        $order = $this->fetchCustomerOrder($userid);
        if (!$order) {
            return false;
        }
        $row = $this->db->table("CUSTOMER_ORDER")
        ->where("ID_CUSTOMER", $userid)
        ->where("ID", $order->id)
        ->fetch(); 
        
        try {
            $this->db->beginTransaction();
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

    /**
     * Change the state of an clients order
     *
     * @param integer $orderId
     * @param string $newState
     * @return boolean
     */
    public function changeClientOrderStatus(int $orderId, string $newState): bool {
        $row = $this->db->table("CUSTOMER_ORDER")->where("ID", $orderId)->fetch();
        if (!$row) {
            return false;
        }
        try {
            $this->db->beginTransaction();
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
