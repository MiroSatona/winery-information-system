<?php
//FILE:     OrderHistoryPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for Order History  

declare(strict_types=1);

namespace App\Presentation\OrderHistory;

use App\Model\Orders\OrderRepository;
use App\Model\Orders\OrderState;
use App\Presentation\BasePresenter;

/**
 * Presenter for order history management
 * Displays customer's past orders and their details
 */
final class OrderHistoryPresenter extends BasePresenter 
{
    /**
     * Constructor for OrderHistoryPresenter
     * @param OrderRepository $orderRepository Repository for accessing order data
     */
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Renders the order history page
     * Displays all past orders for the logged-in customer
     * Redirects to sign-in if user is not authenticated
     * @return void
     */
    public function renderDefault(): void 
    {
        $user = $this->user;

        if (!$user->isLoggedIn()) {
            $this->redirect(":Sign:in");
            return;
        }
        
        $userId = $user->getId();
        $orders = $this->orderRepository->fetchCustomerOrders($userId);

        if (!$orders) {
            $this->template->orders = null;
            $this->template->message = "Nemáte dosud žádné objednávky!";
            return;
        }
        
        $this->template->orders = $orders;
        $this->template->message = '';
        $this->template->OrderState = OrderState::class;
    }
}

