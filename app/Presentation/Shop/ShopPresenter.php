<?php

namespace App\Presentation\Shop;

use Nette;
use App\Model\ProductRepository;
use App\Model\ROLE;

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
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
    }

    public function handleAddToCart(int $id): void
    {
        // For now, just show flash message (you can add real session logic later)
        $this->flashMessage("Wine #$id added to cart!", 'success');
        $this->redirect('this');
    }
} 