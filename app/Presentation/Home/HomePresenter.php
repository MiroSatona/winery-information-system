<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use App\Model\Vineyard\VineyardRepository;
use App\Model\WineVariety\WineVarietyRepository;
use App\Presentation\BasePresenter;

final class HomePresenter extends BasePresenter
{   
    /** @var VineyardRepository $vineyardRepository Repository for getting vineyard data. */
    private VineyardRepository $vineyardRepository;
    /** @var WineVarietyRepository $wineVarietyRepository Repository for getting wine variety data. */
    private WineVarietyRepository $wineVarietyRepository;
    /** @var string $homeImage Path to the home page image. */
    private string $homeImage;
    

    /**
     * Constructor for HomePresenter.
     *
     * @param VineyardRepository $vineyardRepository Repository for vineyard data.
     * @param WineVarietyRepository $wineVarietyRepository Repository for wine variety data.
     */
    public function __construct(VineyardRepository $vineyardRepository, WineVarietyRepository $wineVarietyRepository)
    {
        parent::__construct();
        $this->vineyardRepository = $vineyardRepository;
        $this->wineVarietyRepository = $wineVarietyRepository;
        $this->homeImage = 'images/home_picture.jpg';
    }

    public function startup(): void
    {
        parent::startup();
    }

    public function renderDefault(): void
    {   
        $this->template->homeImage = $this->homeImage;
        $this->template->vineYardsData = $this->vineyardRepository->joinWithVarieties();
        $this->template->wineVarietiesData = $this->wineVarietyRepository->fetchAll();
    }
}
