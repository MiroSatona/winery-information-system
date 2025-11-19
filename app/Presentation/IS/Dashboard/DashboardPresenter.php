<?php

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

final class DashboardPresenter extends BasePresenter
{

    public function __construct(
        private TaskRepository $taskRepository,
        private OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!(  $this->user->isInRole(Role::WORKER->name) ||
                $this->user->isInRole(Role::VINTNER->name) || 
                $this->user->isInRole(Role::ADMIN->name))) {
            $this->redirect(':Home:default');
        }

        $this->template->TaskStateType = TaskState::class;
        $this->template->HarvestType = Harvest::class;
        $this->template->TreatmentType = Treatment::class;
        $this->template->BottlingType = Bottling::class;
        $this->template->currentDateTime = new DateTime('now');
    }

    public function renderView(): void
    {
        // Fetch upcoming tasks (limit 10, planned state, ordered by planned timestamp)
        $upcomingTasks = $this->getUpcomingTasks();
        
        // Fetch ready orders (limit 10, paid state, ordered by timestamp)
        $readyOrders = $this->getReadyOrders();
        
        $this->template->upcomingTasks = $upcomingTasks;
        $this->template->readyOrders = $readyOrders;
    }

    private function getUpcomingTasks(): array
    {
        $allTasks = $this->taskRepository->fetchTasks('', '', TaskState::PLANNED->value);
        
        // Filter tasks that have a planned timestamp
        $tasksWithTimestamp = array_filter($allTasks, function($task) {
            return $task->getPlannedTimestamp() !== null;
        });
        
        // Sort by planned timestamp (earliest first)
        usort($tasksWithTimestamp, function($a, $b) {
            $timestampA = $a->getPlannedTimestamp();
            $timestampB = $b->getPlannedTimestamp();
            
            if ($timestampA === null && $timestampB === null) return 0;
            if ($timestampA === null) return 1;
            if ($timestampB === null) return -1;
            
            return $timestampA <=> $timestampB;
        });
        
        // Return first 10
        return array_slice($tasksWithTimestamp, 0, 10);
    }

    private function getReadyOrders(): array
    {
        $allOrders = $this->orderRepository->fetchAllOrders(true);
        
        // Filter orders awaiting delivery
        $readyOrders = array_filter($allOrders, function($order) {
            return $order->state === OrderState::PAID->value;
        });
        
        // Sort by timestamp
        usort($readyOrders, function($a, $b) {
            return strtotime($a->orderTimestamp) <=> strtotime($b->orderTimestamp);
        });
        
        // Return first 10
        return array_slice($readyOrders, 0, 10);
    }
}
