<?php

namespace App\Presentation\Shop;

use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;
use Nette;
use App\Model\ProductRepository;
use App\Presentation\BasePresenter;
use App\Model\Orders\OrderRepository;
use Tracy\Debugger;

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
    
    private function convertWineTypeToInternal(string $type): ?string 
    {
        switch($type) {
            case "red":
                return "RED";
            case "white":
                return "WHITE";
            case "rose":
                return "PINK";
            default:
                return null;
        }
    }
    
    public function renderDefault(): void
    {
            // Get filter params from GET query
        $search = $this->getParameter('search');
        $type = $this->getParameter('type');
        $sort = $this->getParameter('sort');
        
        $this->template->imageRepository = $this->imageRepository;
       
        // do a simple search if applicable 
        $wines = $this->wineRepository->getAllProducts($search ?? "");
        
        // only allow certain types 
        if ($type) {
            $wines = array_filter($wines, fn($w) => $w->color === $this->convertWineTypeToInternal($type));
        }

        // sort by price 
        if ($sort === 'asc') {
            usort($wines, fn($a, $b) => $a->currentPrice <=> $b->currentPrice);
        } elseif ($sort === 'desc') {
            usort($wines, fn($a, $b) => $b->currentPrice <=> $a->currentPrice);
        }

        // Pass the filter type to Latte
        $this->template->search = $search;
        $this->template->type = $type;
        $this->template->sort = $sort;
        $this->template->wines = $wines;
    }
    
    public function handleAddToCart(int $wineId, int $count = 1): void
    {
        Debugger::log("got this much in the form: $count");
        if (!$this->user->id) {
            return;
        }
        if (!$this->orderRep->addToCart($this->user->id, $wineId, $count)) {
            $this->flashMessage("could not add fine to cart", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Wine #$wineId added to cart!", FlashMsgType::SUCCESS->value);
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