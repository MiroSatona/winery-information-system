<?php
//FILE:     OrdersPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for administration of customer orders
declare(strict_types=1);

namespace App\Presentation\IS\Orders;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Orders\OrderRepository;
use App\Model\FlashMsgType;
use App\Model\Image\ImageRepository;

/**
 * Presenter for administration of customer orders
 * Handles order listing, filtering, state changes, and detail display
 */
final class OrdersPresenter extends BasePresenter
{

    /**
     * Constructor for OrdersPresenter
     * @param OrderRepository $ordRepository Repository for managing orders
     * @param ImageRepository $imgRep Repository for accessing product images
     */
    public function __construct(private OrderRepository $ordRepository, private ImageRepository $imgRep)
    {
        parent::__construct();
    }

    /**
     * Startup method called before any action
     * Checks user role and sets image repository for templates
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
        $this->template->imgRep = $this->imgRep;
    }

    /**
     * Renders the dashboard with order list and filtering
     * @return void
     */
    public function renderDashboard(): void
    {
        $search = $this->getParameter("search");
        $state = $this->getParameter("state");
        $orders = $this->ordRepository->fetchAllOrders(true, $search ?? "");
        if ($state) {
            $orders = array_filter($orders, fn($o) => $o->state === $state);
        }
        $this->template->search = $search;
        $this->template->state = $state;
        $this->template->orders = $orders;
    }
    
    /**
     * Handles changing the state of an order
     * @param int $orderId ID of the order to change
     * @param string $newState New state to set for the order
     * @return void
     */
    public function handleChangeOrderState(int $orderId, string $newState) {
        /** @var bool $result Flag indicating success of state change */
        $result = $this->ordRepository->changeClientOrderStatus($orderId, $newState);
        // Provide feedback to the user based on the result of the state change
        if (!$result) {
            $this->flashMessage("Nepovedlo se upravit stav objednávky", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Změny uloženy", FlashMsgType::SUCCESS->value);
        }
    }
    
     /**
      * Renders the detail view for a specific order
      * @param int $orderId ID of the order to display
      * @return void
      */
     public function renderOrderDetail(int $orderId) {
         $this->template->order = $this->ordRepository->fetchOrderById($orderId, true);
     }
    
    /**
     * Action for changing order state and redirecting to dashboard
     * @param int $orderId ID of the order to change
     * @param string $newState New state to set for the order
     * @return void
     */
    public function actionChangeOrderState(int $orderId, string $newState): void
    {
        $this->handleChangeOrderState($orderId, $newState);
        $this->redirect('dashboard');
    }
}
