<?php

declare(strict_types=1);

namespace App\Presentation\Wine;

use Nette;
use App\Model\ProductRepository;
use App\Model\Security\Role;
use App\Presentation\BasePresenter;
use App\Model\Image\ImageRepository;

final class WinePresenter extends BasePresenter 
{
    private ProductRepository $items;
    private ImageRepository $imageRepository;

    public function __construct(ProductRepository $items, ImageRepository $imageRepository)
    {
        parent::__construct();
        $this->items = $items; 
        $this->imageRepository = $imageRepository;
    }
    public function startup(): void
    {
        parent::startup();
    }
    
    public function renderDetail(int $id): void 
    {

        
        $this->template->wine = $this->items->findById($id); 
        $this->template->imageRepository = $this->imageRepository;
    }
    
    public function handleAddToCart(int $id): void
    {
        // For now, just show flash message (you can add real session logic later)
        $this->flashMessage("Wine #$id added to cart!", 'success');
        $this->redirect('this');
    }
    
}