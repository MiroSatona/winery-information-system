<?php

declare(strict_types=1);

namespace App\Presentation\IS;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\ROLE;

final class ISPresenter extends BasePresenter
{

    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderViewCalendar(): void
    {
        
    }

    public function renderOrdersDashboard(): void
    {
        
    }

    public function renderMainDashboard(): void
    {
        
    }

    public function renderTasksDashboard(): void
    {

    }

    public function renderUsersDashboard(): void
    {

    }

    public function renderVineyardsDashboard(): void
    {

    }

    public function renderWineDashboard(): void
    {

    }

    public function renderWineVarietiesDashboard(): void
    {

    }
}
