<?php

declare(strict_types=1);

namespace App\Presentation;

use Nette;
use Nette\Application\UI\Presenter;
use Nette\Database\Explorer;
use App\Model\ROLE;

class BasePresenter extends Presenter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
    }

    public function renderDefault(): void
    {
        
    }
}
