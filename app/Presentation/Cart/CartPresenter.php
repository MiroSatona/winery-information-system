<?php

declare(strict_types=1);
namespace App\Presentation\Cart;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;

final class CartPresenter extends BasePresenter 
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ImageRepository $imageRepository
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
        
        $this->orderRepository->updateOrderPrice($order->id);

        // Optionally fetch all items for that order
        $order->items = $this->orderRepository
            ->fetchOrderById($order->id, includeItems: true)
            ->items;

        $this->template->order = $order;
        $this->template->imageRepository = $this->imageRepository;
    }

    public function handleRemoveItem(int $wineId): void
    {
        $userId = $this->getUser()->getId();
        
        $success = $this->orderRepository->removeItemFromOrder($userId, $wineId);

        if ($success) {
            $this->flashMessage('Item removed from your cart.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Could not remove item.', FlashMsgType::ERROR->value);
        }

        $this->redirect('this');
    }

    public function handleChangeAmount(int $wineId, bool $add): void
    {
        $userId = $this->getUser()->getId();
        $success = $this->orderRepository->editAmountOrItemInOrder($userId, $wineId, 1, $add);

        if ($success) {
            $this->flashMessage('Item amount updated.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Could not update item amount.', FlashMsgType::ERROR->value); 
        }

        $this->redirect('this');
    }
    
    
}
