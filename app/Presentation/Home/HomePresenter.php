<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use App\Model\ROLE;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function renderDefault(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);
    }
}
