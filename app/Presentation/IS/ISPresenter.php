<?php

declare(strict_types=1);

namespace App\Presentation\IS;

use Nette;
use App\Model\ROLE;

final class ISPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function renderOrdersDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderMainDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderTasksDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderUsersDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderVineyardsDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderWineDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }

    public function renderWineVarietiesDashboard(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
        if (!($this->user->isInRole(ROLE::WORKER->name) || $this->user->isInRole(ROLE::VINTNER->name))) {
            $this->redirect('Home:default');
        }
    }
}
