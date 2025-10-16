<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use App\Model\ROLE;
use App\Model\HomePageContentModel;
use Nette\Application\UI\Presenter;


final class HomePresenter extends Presenter
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
        $this->content = $content;
        $this->homeImage = 'images/home_picture.jpg';
    }

    public function renderDefault(): void
    {
        $this->template->userWorker = $this->user->isInRole(ROLE::WORKER->name);
        $this->template->userVintner = $this->user->isInRole(ROLE::VINTNER->name);
        $this->template->userAdmin = $this->user->isInRole(ROLE::ADMIN->name);
        $this->template->userUser = $this->user->isInRole(ROLE::USER->name);

        $this->template->content = $this->content;
        $this->template->homeImage = $this->homeImage;
      
        
        
    }


}
