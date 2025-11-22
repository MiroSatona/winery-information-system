<?php

//FILE:     DashboardPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for the IS dashboard
declare(strict_types=1);

namespace App\Presentation\IS\Dashboard;

use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Task\TaskRepository;
use App\Model\Task\TaskState;
use App\Model\Task\Harvest\Harvest;
use App\Model\Task\Treatment\Treatment;
use App\Model\Task\Bottling\Bottling;
use App\Model\Orders\OrderRepository;
use App\Model\Orders\OrderState;
use Nette\Utils\DateTime;

/**
 * Presenter for the IS dashboard
 * Handles display of upcoming tasks and ready orders for internal staff
 */
final class DashboardPresenter extends BasePresenter
{
    /**
     * Constructor for DashboardPresenter
     * @param TaskRepository $taskRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private TaskRepository $taskRepository,
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    /**
     * Startup method called before any action
     * Checks user role and sets template types
     * @return void
     */
    public function startup(): void
    {
        parent::startup();

        if (
            !(  $this->user->isInRole(Role::WORKER->name) ||
                $this->user->isInRole(Role::VINTNER->name))
        ) {
            if ($this->user->isInRole(Role::ADMIN->name)) {
                $this->redirect(':IS:Users:dashboard');
            }
            $this->redirect(':Home:default');
        }

        $this->template->TaskStateType = TaskState::class;
        $this->template->HarvestType = Harvest::class;
        $this->template->TreatmentType = Treatment::class;
        $this->template->BottlingType = Bottling::class;
        $this->template->currentDateTime = new DateTime('now');
    }

    /**
     * Renders the dashboard view
     * Loads upcoming tasks and ready orders for display
     * @return void
     */
    public function renderView(): void
    {
        // Fetch upcoming tasks (limit 10, planned state, ordered by planned timestamp)
        $upcomingTasks = $this->getUpcomingTasks();

        // Fetch ready orders (limit 10, paid state, ordered by timestamp)
        $readyOrders = $this->getReadyOrders();

        $this->template->upcomingTasks = $upcomingTasks;
        $this->template->readyOrders = $readyOrders;
    }

    /**
     * Gets first 10 upcoming planned tasks with a planned timestamp
     * @return array List of upcoming tasks
     */
    private function getUpcomingTasks(): array
    {
        $allTasks = $this->taskRepository->fetchTasks('', '', TaskState::PLANNED->value);

        // Filter tasks that have a planned timestamp
        $tasksWithTimestamp = array_filter($allTasks, function ($task) {
            return $task->getPlannedTimestamp() !== null;
        });

        // Sort by planned timestamp (earliest first)
        usort($tasksWithTimestamp, function ($a, $b) {
            $timestampA = $a->getPlannedTimestamp();
            $timestampB = $b->getPlannedTimestamp();

            if ($timestampA === null && $timestampB === null) {
                return 0;
            }
            if ($timestampA === null) {
                return 1;
            }
            if ($timestampB === null) {
                return -1;
            }

            return $timestampA <=> $timestampB;
        });

        // Return first 10
        return array_slice($tasksWithTimestamp, 0, 10);
    }

    /**
     * Gets first 10 ready orders (paid state) sorted by timestamp
     * @return array List of ready orders
     */
    private function getReadyOrders(): array
    {
        $allOrders = $this->orderRepository->fetchAllOrders(true);

        // Filter orders awaiting delivery
        $readyOrders = array_filter($allOrders, function ($order) {
            return $order->state === OrderState::PAID->value;
        });

        // Sort by timestamp
        usort($readyOrders, function ($a, $b) {
            return strtotime($a->orderTimestamp) <=> strtotime($b->orderTimestamp);
        });

        // Return first 10
        return array_slice($readyOrders, 0, 10);
    }
}
