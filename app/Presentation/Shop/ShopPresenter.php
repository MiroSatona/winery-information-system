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
use App\Model\Security\Role;

/**
 * ShopPresenter
 *
 * Presenter for the online wine shop. Handles displaying products, filtering, sorting,
 * and cart operations for the shop section of the application.
 */
final class ShopPresenter extends BasePresenter
{
    /**
     * ShopPresenter constructor
     *
     * @param ProductRepository $wineRepository
     * @param OrderRepository $orderRep
     * @param ImageRepository $imageRepository
     */
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
        
        bdump($type);
        if ($type !== null && $type !== 'ALL') {
            $wines = array_filter($wines, fn($w) => $w->color->value === $type);
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
        $form = new \Nette\Application\UI\Form();
        $form->addHidden('wineId');
        $form->addInteger('count')
            ->setDefaultValue(1)
            ->setRequired()
            ->addRule($form::Min, 'Počet musí být alespoň 1', 1);

        $form->onSuccess[] = [$this, 'addToCartFormSucceeded'];
        return $form;
    }

    /**
     * method for handling add to card function
     *
     * @param \Nette\Application\UI\Form $form
     * @param \stdClass $values
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
