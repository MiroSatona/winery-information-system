<?php

declare(strict_types=1);
namespace App\Presentation\Cart;

use Nette;
use App\Model\Orders\OrderRepository;
use App\Presentation\BasePresenter;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;

final class CartPresenter extends BasePresenter 
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ImageRepository $imageRepository
    ) {
        parent::__construct();
    }

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
        
        $this->orderRepository->updateOrderPrice($order->id);

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

    public function handleRemoveItem(int $wineId): void
    {
        $userId = $this->getUser()->getId();
        
        $success = $this->orderRepository->removeItemFromOrder($userId, $wineId);

        if ($success) {
            $this->flashMessage('Item removed from your cart.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Could not remove item.', FlashMsgType::ERROR->value);
        }

        $this->redirect('this');
    }

    public function handleChangeAmount(int $wineId, bool $add): void
    {
        $userId = $this->getUser()->getId();
        $success = $this->orderRepository->editAmountOrItemInOrder($userId, $wineId, 1, $add);

        if ($success) {
            $this->flashMessage('Item amount updated.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Could not update item amount.', FlashMsgType::ERROR->value); 
        }

        $this->redirect('this');
    }
    
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
