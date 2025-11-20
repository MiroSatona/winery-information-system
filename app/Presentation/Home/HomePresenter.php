<?php
//FILE:     HomePresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for the homepage
/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare(strict_types=1);

namespace App\Presentation\Home;


use Nette;
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
     * @param VineyardRepository $vineyardRepository Repository for accessing vineyard data
     * @param WineVarietyRepository $wineVarietyRepository Repository for accessing wine variety data
     * @param ImageRepository $imageRepository Repository for accessing image data
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
        $this->template->homeImage = $this->homeImage;
        $this->template->vineYardsData = $this->vineyardRepository->joinWithVarieties();
        $this->template->wineVarietiesData = $this->wineVarietyRepository->fetchAll();
        $this->template->imageRepository = $this->imageRepository;
    }
}
