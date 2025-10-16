<?php

declare(strict_types=1);

namespace App\Presentation\IS\Vineyards;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Vineyard\VineyardRepository;
use App\Model\Vineyard\Vineyard;

final class VineyardsPresenter extends BasePresenter
{

    public function __construct(private VineyardRepository $vineyardRepository)
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
        $this->template->vineyards = $this->vineyardRepository->fetchAll();
    }
}
