<?php

namespace App\Presentation\Shop;

use Nette;
use App\Model\ProductRepository;
use App\Presentation\BasePresenter;
use App\Model\Orders\OrderRepository;

final class ShopPresenter extends  BasePresenter
{
    public function __construct(private ProductRepository $wineRepository, private OrderRepository $orderRep) 
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
    }
    
    public function renderDefault(): void
    {
       $this->template->wines = $this->wineRepository->getAllProducts();
    }

    public function handleAddToCart(int $id): void
    {
        // For now, just show flash message (you can add real session logic later)
        $this->flashMessage("Wine #$id added to cart!", 'success');
        $this->redirect('this');
        $this->orderRep->addToCart(1, $id, 1);
    }
} 