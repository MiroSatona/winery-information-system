<?php

declare(strict_types=1);
namespace App\Presentation\Payment;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;

use Nette;


final class PaymentPresenter extends BasePresenter
{
    public function __construct(
        private OrderRepository $orderRep
    ) {}

    public function renderDefault(): void 
    {
        $user = $this->user;

        if (!$user->isLoggedIn()) {
            $this->redirect("Sign:in");
            return;
        }
       
        $userId = $user->getId();
        $order = $this->orderRep->fetchCustomerOrder($userId);
       
        if ($order->totalPrice <= 0) {
            $this->redirect("Home:default");
            return;
        }
        
        $this->template->price = $order->totalPrice;
    }
    
    public function actionConfirm(): void {

        $user = $this->user;

        if (!$user->isLoggedIn()) {
            $this->redirect("Sign:in");
            return;
        }
       
        $userId = $user->getId();
        $result = $this->orderRep->clientPayForOrder($userId);
        $this->redirect("Shop:default");
    }
}

