<?php
//FILE:     WinePresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for WineDetail 
declare(strict_types=1);

namespace App\Presentation\Wine;

use Nette;
use App\Model\Shop\ProductRepository;
use App\Presentation\BasePresenter;
use App\Model\Image\ImageRepository;
use App\Model\FlashMsgType;
use App\Model\Orders\OrderRepository;
use Tracy\Debugger;
use App\Model\Security\Role;

/**
 * Presenter for wine detail page
 * Handles wine display and adding wines to shopping cart
 */
final class WinePresenter extends BasePresenter 
{
    private ProductRepository $items;
    private ImageRepository $imageRepository;

    /**
     * Constructor for WinePresenter
     * @param ProductRepository $items 
     * @param ImageRepository $imageRepository 
     * @param OrderRepository $ordRep 
     */
    public function __construct(ProductRepository $items, ImageRepository $imageRepository, private OrderRepository $ordRep)
    {
        parent::__construct();
        $this->items = $items; 
        $this->imageRepository = $imageRepository;
    }

    /**
     * Startup method called before any action
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
    }
    
    /**
     * Renders the wine detail page
     * @param int $id ID of the wine to display
     * @return void
     */
    public function renderDetail(int $id): void 
    {
        $this->template->wine = $this->items->findById($id); 
        $this->template->imageRepository = $this->imageRepository;
    }
    
    /**
     * Handles adding a wine to the shopping cart
     * @param int $wineId ID of the wine to add
     * @param int $count Quantity to add (default: 1)
     * @return void
     */
    public function handleAddToCart(int $wineId, int $count = 1): void
    {
        $user = $this->user;
        // Check if the user is logged in
        if (!$user->isLoggedIn()) {
            $this->redirect(':Sign:in');
            return;
        }
        
        // user is a customer 
        if (!$user->isInRole(Role::USER->name)) {
            $this->flashMessage("Nejste zákazníkem !", FlashMsgType::ERROR->value);
            $this->redirect(":Home:default");
            return;
        }

        if (!$this->ordRep->addToCart($this->user->id, $wineId, $count)) {
            $this->flashMessage("Položku se nepovedlo přidat do košíku", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Položka přidána do košíku", FlashMsgType::SUCCESS->value);
        }
        
        $this->redirect('this');
    }
    
    /**
     * Creates the add to cart form component
     * @return \Nette\Application\UI\Form Form for adding wine to cart
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
     * Callback for successful add to cart form submission
     * @param \Nette\Application\UI\Form $form The submitted form
     * @param \stdClass $values Form values containing wineId and count
     * @return void
     */
    public function addToCartFormSucceeded(\Nette\Application\UI\Form $form, \stdClass $values): void
    {
        $user = $this->user;
        // Check if the user is logged in
        if (!$user->isLoggedIn()) {
            $this->redirect(':Sign:in');
            return;
        }
        
        // user is a customer 
        if (!$user->isInRole(Role::USER->name)) {
            $this->flashMessage("Nejste zákazníkem !", FlashMsgType::ERROR->value);
            $this->redirect(":Home:default");
            return;
        }

        $this->handleAddToCart((int)$values->wineId, (int)$values->count);
    }
}