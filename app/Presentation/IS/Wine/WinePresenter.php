<?php

declare(strict_types=1);

namespace App\Presentation\IS\Wine;

use App\Model\FlashMsgType;
use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Wine\WineRepository;
use App\Model\Wine\Wine;
use App\Model\Image\ImageRepository;
use App\Model\WineVariety\WineVarietyType;
use App\Model\Wine\WineState;
use Nette\Application\UI\Form;
use App\Model\Validation\ValidationDefinitions;

final class WinePresenter extends BasePresenter
{
    private string $patternWines = '';
    private string $patternType = 'all';
    private string $patternGrade = 'all';
    private string $patternState = 'all';

    public function __construct(
        private WineRepository $wineRepository,
        private ImageRepository $imageRepository
    ) {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
    }

    public function createComponentSearchWineForm(): Form
    {
        $form = new Form();

        $form->addText('search', 'Hledat víno')
            ->setHtmlAttribute('class', 'form-control form-control-sm')
            ->setHtmlAttribute('placeholder', 'Vyhledávejte dle názvu, ročníku, nebo odrůdy ...');

        $form->addSelect('state', 'stav', [
            'all' => 'Všechny',
            'PRODUCTION' => 'Ve výrobě',
            'LIQUIDATED' => 'Zlikvidované',
            'BOTTLED' => 'Zalahvované',
            'SALE' => 'Na prodej',
            'SOLD' => 'Prodáno',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'max-width: 110px;');

        $form->addSelect('grade', 'Přívlastek', [
            'all' => 'Všechny',
            'NONE' => 'Bez přívlastku',
            'CABINET' => 'Kabinetní víno',
            'LATE' => 'Pozdní sběr',
            'GRAPE_SELECTION' => 'Výběr z hroznů',
            'BERRY_SELECTION' => 'Výběr z bobulí',
            'ICE' => 'Ledové víno',
            'STRAW' => 'Slámové víno',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'width: 150px;');

        $form->addSelect('type', 'Typ', [
            'all' => 'Všechny',
            'DRY' => 'Suché',
            'HALF_DRY' => 'Polosuché',
            'HALF_SWEET' => 'Polosladké',
            'SWEET' => 'Sladké',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'width: 110px;');

        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchWineFormSucceeded'];

        return $form;
    }

    public function searchWineFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternWines = $values->search;
        $this->patternType = $values->type;
        $this->patternGrade = $values->grade;
        $this->patternState = $values->state;
    }

    public function renderDashboard(): void
    {
        /** @var Wine[] $wines */
        $wines = $this->wineRepository->fetchAllWinesWithPattern(
            $this->patternWines,
            $this->patternType,
            $this->patternGrade,
            $this->patternState
        );

        $this->template->wines = $wines;
        $this->template->imageRepository = $this->imageRepository;
    }

    public function renderShow(int $wineId): void
    {
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage('Víno nebylo nalezeno', 'error');
            $this->redirect('dashboard');
        }

        $this->wineRepository->fetchBottlingInfoForWine($wine);
        bdump($wine);

        $this->template->wine = $wine;
        $this->template->imageRepository = $this->imageRepository;
        $this->template->WineVarietyType = WineVarietyType::class;
        $this->template->WineState = WineState::class;
    }

    public function handleBottling(int $wineId): void
    {
        // doto 
    }

    public function renderSelling(int $wineId): void
    {
        $wine = $this->wineRepository->fetchWineById($wineId);
        if (!$wine) {
           $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
           $this->redirect(":IS:Wine:dashboard");
           return;
        }

        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může přidávat víno do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        if ($wine->state !== WINeState::bottled->value) {
            $this->flashMessage("Jen víno, které je ve fázi lahvování se může přidat do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }
        
        $this->template->imageRepository = $this->imageRepository;
        $this->template->wine = $wine;
    }
    
 
    public function renderChangePrice(int $wineId): void
    {
        $wine = $this->wineRepository->fetchWineById($wineId);
        if (!$wine) {
           $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
           $this->redirect(":IS:Wine:dashboard");
           return;
        }

        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může přidávat víno do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        if ($wine->state !== WINeState::sale->value) {
            $this->flashMessage("Jen víno, které je ve fázi lahvování se může přidat do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }
        
        $form = $this->getComponent("setWinePriceForm");
        $form["price"]->setValue($wine->getCurrentPrice());
        $form["popis"]->setValue($wine->getSalesDescription());
        
        $this->template->imageRepository = $this->imageRepository;
        $this->template->wine = $wine;
    }
    
    public function createComponentSetWinePriceForm(): Form
    {
        $form = new Form();

        $form->addFloat('price', 'Cena za láhev (Kč)')
            ->addRule(Form::Min, "jsi bot", ValidationDefinitions::PRICE_MIN)
            ->addRule(Form::Max, "jis bot v2", ValidationDefinitions::PRICE_MAX)
            ->setRequired('Zadejte cenu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte cenu např. 249.90');

        $form ->addTextArea("popis", "Prodejní popis")
            ->setRequired("Zadejte prodejní popis");

        $form->addSubmit('send', 'Potvrdit a zařadit do prodeje')
            ->setHtmlAttribute('class', 'btn btn-success');

        $form->onSuccess[] = [$this, 'setWinePriceFormSucceeded'];
        return $form;
    }

    public function setWinePriceFormSucceeded(Form $form, \stdClass $values): void
    {
        $wineId = (int) $this->getParameter('wineId');
        $price = (float) $values->price;
        $description = (string) $values->popis;
        
        $wine = $this->wineRepository->fetchWineById($wineId);
        
        if (!$wine) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);            
            $this->redirect('Wine:dashboard');
            return; 
        }
        
        $result = true;
        if ($wine->state == WineState::bottled->value) {
            $result = $this->wineRepository->updateWinePrice($wineId, $price, false);
            $this->wineRepository->setWineState($wineId, WineState::sale->value);
        }
        $result2 = $this->wineRepository->updateWinePrice($wineId, $price, true);
        $result3 = $this->wineRepository->setWineDescription($wineId, $description);
        
        if (!$result || !$result2 || !$result3) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
        } else {
            $this->flashMessage("Cena uložena", FlashMsgType::SUCCESS->value);
        }

        $this->redirect('Wine:dashboard');
    }

    public function handleChangePrice(int $wineId): void
    {
        // todo
    }

    public function renderEdit(int $wineId): void
    {
        // doto 
    }
    
}
