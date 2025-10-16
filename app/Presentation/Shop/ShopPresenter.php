<?php

namespace App\Presentation\Shop;

use Nette;
use App\Model\ProductRepository;

final class ShopPresenter extends  Nette\Application\UI\Presenter
{
    private ProductRepository $wineRepository;

    public function __construct(ProductRepository $wineRepository) 
    {
      parent::__construct();
      $this->wineRepository = $wineRepository;
    }
    
    public function renderDefault(): void
    {
        // Fetch wines for the shop view
        $this->template->wines = $this->wineRepository->getAllProducts();
    }

    public function handleAddToCart(int $id): void
    {
        // For now, just show flash message (you can add real session logic later)
        $this->flashMessage("Wine #$id added to cart!", 'success');
        $this->redirect('this');
    }
} 