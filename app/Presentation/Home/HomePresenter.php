<?php

//FILE:     HomePresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Presenter for the homepage
declare(strict_types=1);

namespace App\Presentation\Home;

use App\Model\Vineyard\VineyardRepository;
use App\Model\WineVariety\WineVarietyRepository;
use App\Presentation\BasePresenter;
use App\Model\Image\ImageRepository;

/**
 * Presenter for the homepage
 * Handles display of vineyard and wine variety information
 */
final class HomePresenter extends BasePresenter
{
    /** @var VineyardRepository $vineyardRepository Repository for getting vineyard data. */
    private VineyardRepository $vineyardRepository;
    /** @var WineVarietyRepository $wineVarietyRepository Repository for getting wine variety data. */
    private WineVarietyRepository $wineVarietyRepository;
    /** @var string $homeImage Path to the home page image. */
    private string $homeImage;
    /** @var ImageRepository $imageRepository Repository for getting image data. */
    private ImageRepository $imageRepository;

    /**
     * Constructor for HomePresenter
     * @param VineyardRepository $vineyardRepository
     * @param WineVarietyRepository $wineVarietyRepository
     * @param ImageRepository $imageRepository
     */
    public function __construct(VineyardRepository $vineyardRepository, WineVarietyRepository $wineVarietyRepository, ImageRepository $imageRepository)
    {
        parent::__construct();
        $this->vineyardRepository = $vineyardRepository;
        $this->wineVarietyRepository = $wineVarietyRepository;
        $this->imageRepository = $imageRepository;
        $this->homeImage = 'images/home_picture.jpg';
    }

    /**
     * Startup method called before any action
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
    }

    /**
     * Renders the homepage
     * Loads vineyard data, wine varieties, and home page image for display
     * @return void
     */
    public function renderDefault(): void
    {
        // Prepare data for the homepage template
        $this->template->homeImage = $this->homeImage;
        $this->template->vineYardsData = $this->vineyardRepository->joinWithVarieties();
        $this->template->wineVarietiesData = $this->wineVarietyRepository->fetchAll();
        $this->template->imageRepository = $this->imageRepository;
    }
}
