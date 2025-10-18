<?php

declare(strict_types=1);

namespace App\Presentation\IS\Vineyards;

use App\Model\Image\ImageRepository;
use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Vineyard\VineyardRepository;
use App\Model\Vineyard\Vineyard;

final class VineyardsPresenter extends BasePresenter
{

    public function __construct(private VineyardRepository $vineyardRepository, private ImageRepository $imageRepository)
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
        foreach ($this->template->vineyards as $vineyard) {
            /** @var Vineyard $vineyard */
            $vineyardId = $vineyard->getId();
            $this->template->wineRows[$vineyardId] = $this->vineyardRepository->getWineRowsOnVineyard($vineyardId);
            $this->template->wineVarietiesCount[$vineyardId] = $this->vineyardRepository->getWineVarietiesCountOnVineyard($vineyardId);
        }
        $this->template->imageRepository = $this->imageRepository;
    }

    public function handleDelete(int $vineyardId): void
    {
        // todo
    }
}
