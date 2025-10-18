<?php

namespace App\Presentation\Shop;

use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;
use Nette;
use App\Model\ProductRepository;
use App\Presentation\BasePresenter;
use App\Model\Orders\OrderRepository;

final class ShopPresenter extends  BasePresenter
{
    public function __construct(private ProductRepository $wineRepository, private OrderRepository $orderRep, private ImageRepository $imageRepository) 
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
       $this->template->imageRepository = $this->imageRepository;
    }

    public function handleAddToCart(int $wineId): void
    {
        if (!$this->user->id) {
            return;
        }
        if (!$this->orderRep->addToCart($this->user->id, $wineId, 1)) {
            $this->flashMessage("could not add fine to cart", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Wine #$wineId added to cart!", FlashMsgType::SUCCESS->value);
        }
        
        $this->redirect('this');
    }
} 