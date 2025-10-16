<?php

declare(strict_types=1);

namespace App\Presentation\IS\Calendar;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;

final class CalendarPresenter extends BasePresenter
{

    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
    }

    public function renderView(): void
    {
        
    }
}
