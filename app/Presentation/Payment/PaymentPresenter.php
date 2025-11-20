<?php
//FILE:     PaymentPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for they payment method
/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);
namespace App\Presentation\Payment;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;

use Nette;

/**
 * Presenter for payment processing
 * Handles payment display and order confirmation
 */
final class PaymentPresenter extends BasePresenter
{
    /**
     * Constructor for PaymentPresenter
     * @param OrderRepository $orderRep Order repository for managing order operations
     */
    public function __construct(
        private OrderRepository $orderRep
    ) {}

    /**
     * Renders the payment page
     * Displays the total price of the customer's order
     * Redirects to sign-in if not logged in or home if order is empty
     * @return void
     */
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
    
    /**
     * Action to confirm and process the payment
     * Finalizes the order payment and redirects to shop
     * @return void
     */
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

