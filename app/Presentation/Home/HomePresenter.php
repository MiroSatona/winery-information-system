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
        $this->user->isInRole(ROLE::GUEST->name);
    }
}
