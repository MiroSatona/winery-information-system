<?php

declare(strict_types=1);

namespace App\Presentation\Wine;

use Nette;
use App\Model\Shop\ProductRepository;
use App\Model\Security\Role;
use App\Presentation\BasePresenter;
use App\Model\Image\ImageRepository;
use App\Model\FlashMsgType;
use App\Model\Orders\OrderRepository;
use Tracy\Debugger;

final class WinePresenter extends BasePresenter 
{
    private ProductRepository $items;
    private ImageRepository $imageRepository;

    public function __construct(ProductRepository $items, ImageRepository $imageRepository, private OrderRepository $ordRep)
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
    
    
    public function handleAddToCart(int $wineId, int $count = 1): void
    {
        Debugger::log("got this much in the form: $count");
        if (!$this->user->id) {
            return;
        }
        if (!$this->ordRep->addToCart($this->user->id, $wineId, $count)) {
            $this->flashMessage("Položku se nepovedlo přidat do košíku", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Položka přidána do košíku", FlashMsgType::SUCCESS->value);
        }
        
        $this->redirect('this');
    }
    
    protected function createComponentAddToCartForm(): \Nette\Application\UI\Form
    {
        $form = new \Nette\Application\UI\Form;
        $form->addHidden('wineId');
        $form->addInteger('count')
            ->setDefaultValue(1)
            ->setRequired()
            ->addRule($form::Min, 'Počet musí být alespoň 1', 1);

        $form->onSuccess[] = [$this, 'addToCartFormSucceeded'];
        return $form;
    }

    public function addToCartFormSucceeded(\Nette\Application\UI\Form $form, \stdClass $values): void
    {
        $this->handleAddToCart((int)$values->wineId, (int)$values->count);
    }
}