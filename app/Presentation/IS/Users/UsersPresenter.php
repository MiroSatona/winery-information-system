<?php

declare(strict_types=1);

namespace App\Presentation\IS\Users;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;

final class UsersPresenter extends BasePresenter
{

    public function __construct()
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!$this->user->isInRole(Role::ADMIN->name)) {
            $this->redirect(':Home:default');
        }
    }

    public function renderDashboard(): void
    {

    }
}
