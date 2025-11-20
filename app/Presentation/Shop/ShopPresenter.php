<?php
//FILE:     ShopPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for the online wine shop	

namespace App\Presentation\Shop;

use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;
use Nette;
use App\Model\Shop\ProductRepository;
use App\Model\Wine\WineColor;
use App\Presentation\BasePresenter;
use App\Model\Orders\OrderRepository;
use Tracy\Debugger;

final class ShopPresenter extends  BasePresenter
{
    public function __construct(private ProductRepository $wineRepository, private OrderRepository $orderRep, private ImageRepository $imageRepository) 
    {
        parent::__construct();
    }
    /**
     * startup method 
     *
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
    }
   
   /**
    * method to render the shop 
    *
    * @return void
    */ 
    public function renderDefault(): void
    {
            // Get filter params from GET query
        $search = $this->getParameter('search');
        $type = $this->getParameter('type');
        $sort = $this->getParameter('sort');
        
        $this->template->imageRepository = $this->imageRepository;
       
        // do a simple search if applicable 
        $wines = $this->wineRepository->getAllProducts($search ?? "");
        /** @var WineColor|null $color */

        $color = $type->value ?? null;
        // only allow certain types
        if ($color !== null) {
            $wines = array_filter($wines, fn($w) => $w->color === $color);
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
   
    /**
     * Method to handle adding to cart
     *
     * @param integer $wineId
     * @param integer $count
     * @return void
     */
    public function handleAddToCart(int $wineId, int $count = 1): void
    {
        Debugger::log("got this much in the form: $count");
        if (!$this->user->id) {
            return;
        }
        if (!$this->orderRep->addToCart($this->user->id, $wineId, $count)) {
            $this->flashMessage("Nepovedlo se uložení do košíku", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Položka přidána do košíku", FlashMsgType::SUCCESS->value);
        }
        
        $this->redirect('this');
    }
    
    /**
     * method to create add to cart form  
     *
     * @return \Nette\Application\UI\Form
     */
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

    /**
     * method for handling add to card functino 
     *
     * @param \Nette\Application\UI\Form $form
     * @param \stdClass $values
     * @return void
     */
    public function addToCartFormSucceeded(\Nette\Application\UI\Form $form, \stdClass $values): void
    {
        $this->handleAddToCart((int)$values->wineId, (int)$values->count);
    }


} 