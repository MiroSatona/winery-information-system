<?php

//FILE:     TaskRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     A base presenter class

declare(strict_types=1);

namespace App\Presentation;

use Nette\Application\UI\Presenter;
use App\Model\Security\Role;

/**
 * Base Presenter class that setups user roles for templates
 */
class BasePresenter extends Presenter
{
    /**
     * Base Presenter constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Base Presenter startup method
     *
     * Configures user roles for template usage
     *
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
        $this->template->userWorker = $this->user->isInRole(Role::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(Role::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(Role::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(Role::USER->name);
    }
}
