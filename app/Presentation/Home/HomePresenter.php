<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use App\Model\HomePageContentModel;
use App\Presentation\BasePresenter;

final class HomePresenter extends BasePresenter
{   
    /** @var HomePageContentModel $content Content model for getting home page content about vineyards and wine varieties. */
    private HomePageContentModel $content;
    /** @var string $homeImage Path to the home page image. */
    private string $homeImage;
    

    /**
     * Constructor for HomePresenter.
     *
     * @param HomePageContentModel $content Content model for getting home page content about vineyards and wine varieties.
     */
    public function __construct(HomePageContentModel $content)
    {
        parent::__construct();
        $this->content = $content;
        $this->homeImage = 'images/home_picture.jpg';
    }

    public function startup(): void
    {
        parent::startup();
    }

    public function renderDefault(): void
    {
        $this->template->content = $this->content;
        $this->template->homeImage = $this->homeImage;
    }


}
