<?php

declare(strict_types=1);

namespace App\Presentation\OrderHistory;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;

final class OrderHistoryPresenter extends BasePresenter 
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ImageRepository $imgRepository
    ) {}

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
        
    }
}

