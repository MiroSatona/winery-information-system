<?php

declare(strict_types=1);
namespace App\Presentation\Cart;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;

final class CartPresenter extends BasePresenter 
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $user = $this->user;

        if (!$user->isLoggedIn()) {
            $this->redirect('Sign:in');
            return;
        }

        $userId = $user->getId();
        $order = $this->orderRepository->fetchCustomerOrder($userId);

        if (!$order) {
            $this->template->order = null;
            $this->template->message = 'Your cart is empty.';
            return;
        }

        // Optionally fetch all items for that order
        $order->items = $this->orderRepository
            ->fetchOrderById($order->id, includeItems: true)
            ->items;

        $this->template->order = $order;
    }

    public function handleRemoveItem(int $itemId): void
    {
        $userId = $this->getUser()->getId();
        $success = $this->orderRepository->removeItemFromOrder($userId, $itemId);

        if ($success) {
            $this->flashMessage('Item removed from your cart.', 'success');
        } else {
            $this->flashMessage('Could not remove item.', 'error');
        }

        $this->redirect('this');
    }

    public function handleChangeAmount(int $itemId, int $newAmount): void
    {
        $userId = $this->getUser()->getId();
        $success = $this->orderRepository->editAmountOrItemInOrder($userId, $itemId, $newAmount);

        if ($success) {
            $this->flashMessage('Item amount updated.', 'success');
        } else {
            $this->flashMessage('Could not update item amount.', 'error');
        }

        $this->redirect('this');
    }
    
    
}
