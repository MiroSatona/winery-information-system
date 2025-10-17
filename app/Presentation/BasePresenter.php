<?php

declare(strict_types=1);

namespace App\Presentation;

use Nette;
use Nette\Application\UI\Presenter;
use App\Model\Security\Role;

class BasePresenter extends Presenter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        $this->template->userWorker = $this->user->isInRole(Role::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(Role::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(Role::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(Role::USER->name);
    }

    public function renderDefault(): void
    {
        
    }
}
