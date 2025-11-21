<?php
//FILE:     CartPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for users shopping carts. 	

declare(strict_types=1);
namespace App\Presentation\Cart;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;
use App\Model\User\User;
use App\Model\Orders\Order;
use Nette\Application\UI\Form;

/**
 * Presenter for shopping cart management.
 * Handles cart display, item management, and delivery options.
 */
final class CartPresenter extends BasePresenter 
{
    /**
     * Constructor for CartPresenter
     * @param OrderRepository $orderRepository Repository for managing orders and cart operations.
     * @param ImageRepository $imageRepository Repository for accessing product images.
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private ImageRepository $imageRepository
    ) {
        parent::__construct();
    }

    /**
     * Renders the shopping cart page.
     * Displays current order items, prices, and delivery options.
     * Redirects to sign-in if user is not authenticated.
     * @return void
     */
    public function renderDefault(): void
    {   
        /** @var User $user Actual user. */
        $user = $this->user;

        // Check if the user is logged in
        if (!$user->isLoggedIn()) {
            $this->redirect(':Sign:in');
            return;
        }

        /** @var int $userId ID of the current user. */
        $userId = $user->getId();
        /** @var Order|null $order Array of orders for the user. */
        $order = $this->orderRepository->fetchCustomerOrder($userId);

        // Check if the order exists
        if (!$order) {
            $this->template->order = null;
            $this->template->message = 'nákupní košík je prázdný';
            return;
        }

        // Update the total price of the order
        $this->orderRepository->updateOrderPrice($order->id, true);

        // Fetch all items for this order
        $order->items = $this->orderRepository
            ->fetchOrderById($order->id, includeItems: true)
            ->items;

        // Give template a order and image repository for rendering
        $this->template->order = $order;
        $this->template->imageRepository = $this->imageRepository;

        // State of the delivery
        $order->deliveryType = $order->deliveryType;
        // Data about delivery
        $order->deliveryData = $order->deliveryData ?? '';
        // Give template the updated order
        $this->template->order = $order;
    }

    /**
     * Handles removal of an item from the cart
     * @param int $wineId ID of the wine to remove from cart
     * @return void
     */
    public function handleRemoveItem(int $wineId): void
    {
        /** @var int $userId ID of the current user. */
        $userId = $this->getUser()->getId();

        /** @var bool $success Flag of success for item removal. */
        $success = $this->orderRepository->removeItemFromOrder($userId, $wineId);

        // Handle success or failure of item removal
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
        /** @var int $userId ID of the current user. */
        $userId = $this->getUser()->getId();
        /** @var bool $success Flag of success for changing item amount. */
        $success = $this->orderRepository->editAmountOrItemInOrder($userId, $wineId, 1, $add);

        // Handle success or failure of changing item amount
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
     * @return Form Form for delivery options
     */
    protected function createComponentDeliveryForm(): Form
    {   
        /** @var Form $form Form for delivery options. */
        $form = new Form;

        /** @var int $userId ID of the current user. */
        $userId = $this->getUser()->getId();
        /** @var CostumerOrder $order Order of the current user. */
        $order = $this->orderRepository->fetchCustomerOrder($userId);

        /** @var string|null $deliveryType Delivery type of the current order. */
        $deliveryType = $order->deliveryType?? 'PICKUP';
        /** @var string|null $address Delivery address of the current order. */
        $address = $order->deliveryData?? null;

        // Form for delivery options
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
        /** @var string $type Delivery type from the form. */
        $type= $values->deliveryType;
        /** @var string|null $address Delivery address from the form. */
        $address = $values->address ?: null;
        /** @var int $userId ID of the current user. */
        $userId = $this->getUser()->getId();

        // For PICKUP clear address
        if ($address === 'PICKUP') {
            $address = null;
        }

        /** @var bool $result Flag of success for updating delivery address. */
        $result = $this->orderRepository->updateDeliveryAddress($userId, $type, $address);
        // Handle success or failure of updating delivery address
        if ($result) {
            $this->flashMessage("Způsob doručení byl uložen", FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage("Způsob doručení byl uložen", FlashMsgType::ERROR->value);
        }
        
        $this->redirect('this');
    }
}
