<?php

declare(strict_types=1);

namespace App\Presentation\IS\WineVarieties;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Wine;
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\WineVariety\WineVarietyType;
use App\Model\WineVariety\WineVariety;

final class WineVarietiesPresenter extends BasePresenter
{

    public function __construct(private WineVarietyRepository $wineVarietyRepository)
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

    public function renderDashboard(): void
    {
        $this->template->wineVarieties = $this->wineVarietyRepository->fetchAll();
    }
}
