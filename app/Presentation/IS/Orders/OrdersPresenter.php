<?php

declare(strict_types=1);

namespace App\Presentation\IS\Orders;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Orders\OrderRepository;
use App\Model\FlashMsgType;

final class OrdersPresenter extends BasePresenter
{

    public function __construct(private OrderRepository $ordRepository)
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
    }

    public function renderDashboard(): void
    {
        
        $search = $this->getParameter("search");
        $state = $this->getParameter("state");
        
        $orders = $this->ordRepository->fetchAllOrders(true, $search ?? "");

        if ($state) {
            $orders = array_filter($orders, fn($o) => $o->state === $state);
        }
        
        $this->template->search = $search;
        $this->template->state = $state;
        $this->template->orders = $orders;
    }
    
    public function handleChangeState(int $orderId, string $newState) {
        
        $result = $this->ordRepository->changeClientOrderStatus($orderId, $newState);
        
        if (!$result) {
            $this->flashMessage("Nepovedlo se upravit stav objednávky", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Změny uloženy", FlashMsgType::SUCCESS->value);
        }
    }
    

}
