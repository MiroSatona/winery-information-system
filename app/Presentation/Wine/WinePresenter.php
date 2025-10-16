<?php

declare(strict_types=1);

namespace App\Presentation\Wine;

use Nette;
use App\Model\ProductRepository;
use App\Model\ROLE;

final class WinePresenter extends Nette\Application\UI\Presenter
{
    private ProductRepository $items;

    public function __construct(ProductRepository $items)
    {
        parent::__construct();
        $this->items = $items; 
    }
    
    public function renderDetail(int $id): void 
    {
       $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
  
        $this->template->wine = $this->items->findById($id); 
    }
    
}