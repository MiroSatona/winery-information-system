<?php

declare(strict_types=1);

namespace App\Presentation\Wine;

use Nette;
use App\Model\ProductRepository;
use App\Model\Security\Role;
use App\Presentation\BasePresenter;

final class WinePresenter extends BasePresenter 
{
    private ProductRepository $items;

    public function __construct(ProductRepository $items)
    {
        parent::__construct();
        $this->items = $items; 
    }
    public function startup(): void
    {
        parent::startup();
    }
    
    public function renderDetail(int $id): void 
    {

        
        $this->template->wine = $this->items->findById($id); 
    }
    
    public function handleAddToCart(int $id): void
    {
        // For now, just show flash message (you can add real session logic later)
        $this->flashMessage("Wine #$id added to cart!", 'success');
        $this->redirect('this');
    }
    
}