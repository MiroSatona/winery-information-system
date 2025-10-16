<?php

namespace App\Presentation\Shop;

use Nette;
use App\Model\ProductRepository;
use App\Model\ROLE;
use App\Presentation\BasePresenter;

final class ShopPresenter extends  BasePresenter
{
    private ProductRepository $wineRepository;

    public function __construct(ProductRepository $wineRepository) 
    {
      parent::__construct();
      $this->wineRepository = $wineRepository;
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
    }
} 