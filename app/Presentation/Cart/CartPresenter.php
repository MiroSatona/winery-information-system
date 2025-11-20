<?php
//FILE:     CartPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for users shopping cart 	

declare(strict_types=1);
namespace App\Presentation\Cart;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;

/**
 * Presenter for shopping cart management
 * Handles cart display, item management, and delivery options
 */
final class CartPresenter extends BasePresenter 
{
    /**
     * Constructor for CartPresenter
     * @param OrderRepository $orderRepository Repository for managing orders and cart operations
     * @param ImageRepository $imageRepository Repository for accessing product images
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private ImageRepository $imageRepository
    ) {
        parent::__construct();
    }

    /**
     * Renders the shopping cart page
     * Displays current order items, prices, and delivery options
     * Redirects to sign-in if user is not authenticated
     * @return void
     */
    public function renderDefault(): void
    {
        $user = $this->user;

        if (!$user->isLoggedIn()) {
            $this->redirect(':Sign:in');
            return;
        }

        $userId = $user->getId();
        $order = $this->orderRepository->fetchCustomerOrder($userId);

        if (!$order) {
            $this->template->order = null;
            $this->template->message = 'nákupní košík je prázdný';
            return;
        }
        $this->orderRepository->updateOrderPrice($order->id, true);

        // Optionally fetch all items for that order
        $order->items = $this->orderRepository
            ->fetchOrderById($order->id, includeItems: true)
            ->items;

        $this->template->order = $order;
        $this->template->imageRepository = $this->imageRepository;

        $order->deliveryType = $order->deliveryType; // assuming STATE holds delivery type
        $order->deliveryData = $order->deliveryData ?? '';
        $this->template->order = $order;
    }

    /**
     * Handles removal of an item from the cart
     * @param int $wineId ID of the wine to remove from cart
     * @return void
     */
    public function handleRemoveItem(int $wineId): void
    {
        $userId = $this->getUser()->getId();
        
        $success = $this->orderRepository->removeItemFromOrder($userId, $wineId);

        if ($success) {
            $this->flashMessage('Položka', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Položku nelze odebrat!', FlashMsgType::ERROR->value);
        }

        $this->redirect('this');
    }

    /**
     * Handles changing the quantity of an item in the cart
     * @param int $wineId ID of the wine to modify
     * @param bool $add True to increase quantity, false to decrease
     * @return void
     */
    public function handleChangeAmount(int $wineId, bool $add): void
    {
        $userId = $this->getUser()->getId();
        $success = $this->orderRepository->editAmountOrItemInOrder($userId, $wineId, 1, $add);

        if ($success) {
            $this->flashMessage('Změna počtu položky uložena', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Uložení se nepovedlo', FlashMsgType::ERROR->value); 
        }

        $this->redirect('this');
    }
    
    /**
     * Creates the delivery options form component
     * Form allows selecting delivery type and entering delivery address
     * @return \Nette\Application\UI\Form Form for delivery options
     */
    protected function createComponentDeliveryForm(): \Nette\Application\UI\Form
    {
        $form = new \Nette\Application\UI\Form;

        // Fetch current order for the user
        $userId = $this->getUser()->getId();
        $order = $this->orderRepository->fetchCustomerOrder($userId);

        $deliveryType = $order->deliveryType?? 'PICKUP';
        $address = $order->deliveryData?? null;

        $form->addRadioList('deliveryType', 'Způsob doručení', [
            'PICKUP' => 'Osobní odběr',
            'COURIER' => 'Kurýr',
            'POST' => 'Pošta',
        ])
            ->setDefaultValue($deliveryType)
            ->setRequired();

        $form->addText('address', 'Adresa doručení:')
            ->setNullable()
            ->setDefaultValue($address);

        $form->addSubmit('save', 'Uložit způsob doručení')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = [$this, 'deliveryFormSucceeded'];
        return $form;
    }

    /**
     * Callback for successful delivery form submission
     * Saves the selected delivery type and address to the order
     * @param \Nette\Application\UI\Form $form The submitted form
     * @param \stdClass $values Form values containing deliveryType and address
     * @return void
     */
    public function deliveryFormSucceeded(\Nette\Application\UI\Form $form, \stdClass $values): void
    {
        $type= $values->deliveryType;
        $address = $values->address ?: null;

        $userId = $this->getUser()->getId();

        // For PICKUP, clear address
        if ($address === 'PICKUP') {
            $address = null;
        }

        $result = $this->orderRepository->updateDeliveryAdress($userId, $type, $address);
        if ($result) {
            $this->flashMessage("Způsob doručení byl uložen", FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage("Způsob doručení byl uložen", FlashMsgType::ERROR->value);
        }
        
        $this->redirect('this');
    }
}
