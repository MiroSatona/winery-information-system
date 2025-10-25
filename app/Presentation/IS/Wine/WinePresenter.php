<?php

declare(strict_types=1);

namespace App\Presentation\IS\Wine;

use App\Model\FlashMsgType;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Wine\WineRepository;
use App\Model\Wine\Wine;
use App\Model\Image\ImageRepository;
use App\Model\Task\Harvest\HarvestRepository;
use App\Model\Validation\ValidationDefinitions;
use App\Model\Wine\WineProduceData;
use App\Model\WineVariety\WineVarietyType;
use App\Model\Wine\WineState;
use App\Model\Wine\WineGrade;
use App\Model\Wine\WineType;
use App\Model\Wine\WineColor;
use App\Model\Image\Image;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\Button;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

final class WinePresenter extends BasePresenter
{
    private string $patternWines = '';
    private string $patternType = 'all';
    private string $patternGrade = 'all';
    private string $patternState = 'all';

    public function __construct(
        private WineRepository $wineRepository,
        private ImageRepository $imageRepository,
        private HarvestRepository $harvestRepository
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
            WineState::PRODUCTION->value => 'Ve výrobě',
            WineState::LIQUIDATED->value => 'Zlikvidované',
            WineState::BOTTLED->value => 'Zalahvované',
            WineState::SALE->value => 'Na prodej',
            WineState::SOLD->value => 'Prodáno',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'max-width: 100px;');

        $form->addSelect('grade', 'Přívlastek', [
            'all' => 'Všechny',
            WineGrade::NONE->value => 'Bez přívlastku',
            WineGrade::CABINET->value => 'Kabinetní víno',
            WineGrade::LATE->value => 'Pozdní sběr',
            WineGrade::GRAPE_SELECTION->value => 'Výběr z hroznů',
            WineGrade::BERRY_SELECTION->value => 'Výběr z bobulí',
            WineGrade::ICE->value => 'Ledové víno',
            WineGrade::STRAW->value => 'Slámové víno',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'max-width: 100px;');

        $form->addSelect('type', 'Typ', [
            'all' => 'Všechny',
            WineType::DRY->value => 'Suché',
            WineType::HALF_DRY->value => 'Polosuché',
            WineType::HALF_SWEET->value => 'Polosladké',
            WineType::SWEET->value => 'Sladké',
        ])
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setDefaultValue('all')
            ->setHtmlAttribute('style', 'max-width: 100px;');

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
            $this->flashMessage('Víno nebylo nalezeno', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        $this->wineRepository->fetchBottlingInfoForWine($wine);

        $this->template->wine = $wine;
        $this->template->imageRepository = $this->imageRepository;
        $this->template->WineVarietyType = WineVarietyType::class;
        $this->template->WineState = WineState::class;
    }

    public function handleLiquidate(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('K likvidaci vína nemáte oprávnění', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }

        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage('Víno nebylo nalezeno', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }

        if ($wine->state !== WineState::PRODUCTION) {
            $this->flashMessage('Lze likvidovat pouze víno ve výrobě', FlashMsgType::ERROR->value);
            $this->redirect('this');
            return;
        }

        $result = $this->wineRepository->liquidateWine($wineId, $this->user->getId());
        if ($result) {
            $this->flashMessage('Víno bylo úspěšně zlikvidováno', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Při likvidaci vína došlo k chybě', FlashMsgType::ERROR->value);
        }
    }

    public function handleBottling(int $wineId): void
    {
        // todo
    }

    public function renderSelling(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může přidávat víno do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $wine = $this->wineRepository->fetchWineById($wineId);
        if (!$wine) {
           $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
           $this->redirect(":IS:Wine:dashboard");
           return;
        }

        if ($wine->state !== WINeState::BOTTLED->value) {
            $this->flashMessage("Jen víno, které je ve fázi lahvování se může přidat do prodeje!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }
        
        $this->template->imageRepository = $this->imageRepository;
        $this->template->wine = $wine;
    }
    
 
    public function renderChangePrice(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může přidávat víno do prodeje a měnit cenu!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }
        
        $wine = $this->wineRepository->fetchWineById($wineId);
        if (!$wine) {
           $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
           $this->redirect(":IS:Wine:dashboard");
           return;
        }

        if ($wine->state !== WineState::SALE->value) {
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

        $form->addSubmit('send', 'Potvrdit')
            ->setHtmlAttribute('class', 'btn btn-success');

        $form->onSuccess[] = [$this, 'setWinePriceFormSucceeded'];
        return $form;
    }

    public function setWinePriceFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může přidávat víno do prodeje a měnit cenu!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

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
        if ($wine->state == WineState::BOTTLED->value) {
            $result = $this->wineRepository->updateWinePrice($wineId, $price, false);
            $this->wineRepository->setWineState($wineId, WineState::SALE->value);
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

    public function createComponentEditWineForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Název vína')
            ->addRule(Form::MinLength, 'Název vína musí mít alespoň %d znaky.', ValidationDefinitions::NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, 'Název vína může mít maximálně %d znaků.', ValidationDefinitions::NAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vína...')
            ->setRequired('Zadejte název vína.');
        
        $form->addInteger('year', 'Ročník')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. ' . date('Y'))
            ->setRequired('Zadejte ročník vína.')
            ->setValue(date('Y'))
            ->addRule(Form::Min, 'Ročník nemůže být menší než %d.', ValidationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, 'Ročník nemůže být větší než aktuální rok', date('Y'));
        
        $form->addFloat('alcoholPercentage', 'Obsah alkoholu (%)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 13.5')
            ->setValue(13.5)
            ->setRequired('Zadejte obsah alkoholu ve víně.')
            ->addRule(Form::Min, 'Obsah alkoholu nemůže být menší než %d.', ValidationDefinitions::ALCOHOL_CONTENT_MIN)
            ->addRule(Form::Max, 'Obsah alkoholu nemůže být větší než %d.', ValidationDefinitions::ALCOHOL_CONTENT_MAX);

        $form->addSelect('type', 'Typ vína', [
            WineType::DRY->value => 'Suché',
            WineType::HALF_DRY->value => 'Polosuché',
            WineType::HALF_SWEET->value => 'Polosladké',
            WineType::SWEET->value => 'Sladké',
        ])
            ->setValue(WineType::DRY->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte typ vína.');

        $form->addSelect('color', 'Barva vína', [
            WineColor::WHITE->value => 'Bílé',
            WineColor::ROSE->value => 'Růžové',
            WineColor::RED->value => 'Červené',
        ])
            ->setValue(WineColor::WHITE->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte barvu vína.');
        
        $form->addSelect('grade', 'Přívlastek vína', [
            WineGrade::NONE->value => 'Bez přívlastku',
            WineGrade::CABINET->value => 'Kabinetní víno',
            WineGrade::LATE->value => 'Pozdní sběr',
            WineGrade::GRAPE_SELECTION->value => 'Výběr z hroznů',
            WineGrade::BERRY_SELECTION->value => 'Výběr z bobulí',
            WineGrade::ICE->value => 'Ledové víno',
            WineGrade::STRAW->value => 'Slámové víno',
        ])
            ->setValue(WineGrade::NONE->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte přívlastek vína.');
        
        $form->addInteger('volume', 'Objem výroby (litry)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 500')
            ->setRequired('Zadejte objem výroby vína v litrech.')
            ->addRule(Form::Min, 'Objem výroby nemůže být menší než %d litrů.', ValidationDefinitions::VOLUME_MIN)
            ->addRule(Form::Max, 'Objem výroby nemůže být větší než %d litrů.', ValidationDefinitions::VOLUME_MAX);
        
        $form->addFloat('leftSugar', 'Zbytkový cukr (g/l)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 4.5')
            ->setRequired('Zadejte hodnotu zbytkového cukru ve víně.')
            ->addRule(Form::Min, 'Zbytkový cukr nemůže být menší než %d g/l.', ValidationDefinitions::SUGAR_CONTENT_MIN)
            ->addRule(Form::Max, 'Zbytkový cukr nemůže být větší než %d g/l.', ValidationDefinitions::SUGAR_CONTENT_MAX);

        $form->addFloat('acidContents', 'Kyseliny (g/l)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 6.2')
            ->setRequired('Zadejte hodnotu kyselin ve víně.')
            ->addRule(Form::Min, 'Kyseliny nemohou být menší než %d g/l.', ValidationDefinitions::ACID_CONTENTS_MIN)
            ->addRule(Form::Max, 'Kyseliny nemohou být větší než %d g/l.', ValidationDefinitions::ACID_CONTENTS_MAX);

        $form->addTextArea('productionNote', 'Poznámka k výrobě')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', '3')
            ->setHtmlAttribute('placeholder', 'Volitelná poznámka k výrobnímu procesu...');
        
        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');

        $form->onSuccess[] = [$this, 'wineEditFormSucceeded'];

        return $form;
    }

    public function wineEditFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může upravovat víno!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $wineId = (int) $this->getParameter('wineId');
        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
            $this->redirect('Wine:dashboard');
            return;
        }

        /** @var Wine $newWine */
        $newWine = new Wine(
            $wine->getId(),
            $wine->getIdLiquidator(),
            $wine->getIdSellApprover(),
            $values->year,
            $values->alcoholPercentage,
            WineType::from($values->type),
            WineColor::from($values->color),
            WineGrade::from($values->grade),
            $wine->getState(),
            $values->volume,
            $wine->getBottlePrice(),
            $wine->getCurrentPrice(),
            $wine->getBottlesLeft(),
            $values->leftSugar,
            $values->acidContents,
            $wine->getSalesDescription(),
            $values->productionNote,
            $values->name,
            $wine->getImage(),
            [],
            []
        );

        $result = $this->wineRepository->updateWine($newWine);
        if ($result) {
            $this->flashMessage("Víno bylo úspěšně upraveno", FlashMsgType::SUCCESS->value);
            $this->redirect('Wine:show', $wineId);
        } else {
            $this->flashMessage("Víno se nepodařilo upravit", FlashMsgType::ERROR->value);
            $this->redirect('Wine:edit', $wineId);
        }
    }

    public function renderEdit(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může upravovat víno!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage('Víno nebylo nalezeno', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        
        // Populate form with current values
        $form = $this->getComponent('editWineForm');
        $form['name']->setValue($wine->getName());
        $form['year']->setValue($wine->getYear());
        $form['alcoholPercentage']->setValue($wine->getAlcoholPercentage());
        $form['type']->setValue($wine->getType()->value);
        $form['color']->setValue($wine->getColor()->value);
        $form['grade']->setValue($wine->getGrade()->value);
        $form['volume']->setValue($wine->getVolume());
        $form['leftSugar']->setValue($wine->getLeftSugar());
        $form['acidContents']->setValue($wine->getAcidContents());
        $form['productionNote']->setValue($wine->getProductionNote());

        $this->template->wine = $wine;
    }

    public function createComponentChangeImageForm(): Form
    {
        $form = new Form();

        $form->addUpload('image', 'Obrázek vína')
            ->setRequired('Vyberte obrázek k nahrání.')
            ->addRule(Form::Image, 'Vložte platný obrázek.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('accept', 'image/*');
        
        $form->addSubmit('send', 'Uložit obrázek')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, 'changeWineImage'];
        
        $form->addSubmit('cancel', 'Zrušit')
            ->setHtmlAttribute('class', 'btn btn-outline-secondary w-100 fw-bold')
            ->setValidationScope([])
            ->onClick[] = [$this, 'backToDashboardImage'];
        
        return $form;
    }

    public function changeWineImage(Button $button, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může měnit obrázek vína!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $wineId = (int) $this->getParameter('wineId');
        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
            $this->redirect('Wine:dashboard');
            return;
        }

        /** @var FileUpload $image */
        $image = $values->image;
        /** @var Image $imageEntity */
        $imageEntity = null;
        // Handle new image upload
        if ($image->hasFile()) {
            $imagePath = $this->imageRepository->uploadImage($image);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $this->flashMessage("Obrázek se nepodařilo nahrát", FlashMsgType::ERROR->value);
                return;
            }
            $imageEntity = new Image(null, $imagePath, $wine->getName(), 'Obrázek vinohradu ' . $wine->getName());
        }

        $result = $this->wineRepository->updateWineImage($wineId, $imageEntity);
        if ($result) {
            $this->flashMessage("Obrázek vína byl úspěšně změněn", FlashMsgType::SUCCESS->value);
            $this->redirect('Wine:show', $wineId);
        } else {
            $this->flashMessage("Obrázek vína se nepodařilo změnit", FlashMsgType::ERROR->value);
            $this->redirect('Wine:changeImage', $wineId);
        }
    }

    public function removeWineImage(Button $button, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může měnit obrázek vína!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $wineId = (int) $this->getParameter('wineId');
        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
            $this->redirect('Wine:dashboard');
            return;
        }

        if ($wine->getImage() === null) {
            $this->flashMessage("Víno nemá přiřazený obrázek", FlashMsgType::ERROR->value);
            $this->redirect('Wine:show', $wineId);
            return;
        }

        $this->imageRepository->removeImage($wine->getImage()->id);

        $result = $this->wineRepository->updateWineImage($wineId, null);
        if ($result) {
            $this->flashMessage("Obrázek vína byl úspěšně odstraněn", FlashMsgType::SUCCESS->value);
            $this->redirect('Wine:show', $wineId);
        } else {
            $this->flashMessage("Obrázek vína se nepodařilo odstranit", FlashMsgType::ERROR->value);
            $this->redirect('Wine:changeImage', $wineId);
        }
    }

    public function handleRemoveWineImage(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může měnit obrázek vína!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        /** @var Wine $wine */
        $wine = $this->wineRepository->fetchWineById($wineId);
        if ($wine === null) {
            $this->flashMessage("Něco se pokazilo", FlashMsgType::ERROR->value);
            $this->redirect('Wine:dashboard');
            return;
        }

        if ($wine->getImage() === null) {
            $this->flashMessage("Víno nemá přiřazený obrázek", FlashMsgType::WARNING->value);
            $this->redirect('changeImage', $wineId);
            return;
        }

        // Remove the physical file
        $this->imageRepository->removeImage($wine->getImage()->id);

        // Update the wine record to remove image reference
        $result = $this->wineRepository->updateWineImage($wineId, null);
        if ($result) {
            $this->flashMessage("Obrázek vína byl úspěšně odstraněn", FlashMsgType::SUCCESS->value);
            $this->redirect('show', $wineId);
        } else {
            $this->flashMessage("Obrázek vína se nepodařilo odstranit", FlashMsgType::ERROR->value);
            $this->redirect('changeImage', $wineId);
        }
    }

    public function renderChangeImage(int $wineId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("Jen Vinař může měnit obrázek vína!", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $this->template->imageRepository = $this->imageRepository;
        $this->template->wine = $this->wineRepository->fetchWineById($wineId);
    }

    public function renderProduceP1(): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $harvests = $this->getSession('wineIS')->get('harvestsInProduction');
        $this->template->harvestsInProduction = $harvests ?? [];
    }

    public function createComponentHarvestSelectionForm(): Form
    {
        $form = new Form();

        /** @var Harvest[] $harvests */
        $harvests = $this->harvestRepository->fetchAllHarvestsReadyForProduction();
        $harvestOptions = [];

        $harvestsSelected = $this->getSession('wineIS')->get('harvestsInProduction');
        if ($harvestsSelected === null) {
            $harvestsSelected = [];
        }
        
        foreach ($harvests as $harvest) {
            /** @var Harvest $harvest */
            foreach ($harvestsSelected as $selectedHarvest) {
                if ($selectedHarvest->getId() === $harvest->getId()) {
                    continue 2; // Skip already selected harvests
                }
            }

            $harvestOptions[$harvest->getId()] = 
            "Sklizeň " . $harvest->getWineVariety()->getName() . 
            " | " . $harvest->getVineyard()->getName() .
            " | " . $harvest->getExecutionTimestamp()->format('d.m.Y') .
            " | " . ($harvest->getWeight()) . " kg" .
            " | " . ($harvest->getSugarContent()) . " °NM";
        }

        $form->addSelect('harvest', 'Sklizeň', $harvestOptions)
            ->setPrompt('Vyberte sklizeň ...')
            ->setHtmlAttribute('class', 'form-select');

        $form->addSubmit('backToDashboard', 'Zrušit')
            ->setHtmlAttribute('class', 'btn btn-outline-secondary w-100 fw-bold')
            ->setValidationScope([])
            ->onClick[] = [$this, 'backToDashboardProduce'];

        $form->addSubmit('addHarvest', 'Přidat sklizeň')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, 'harvestSelectionFormSucceeded'];
        
        $form->addSubmit('toStep2', 'Pokračovat k výrobě')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, 'redirectToProduceP2'];

        return $form;
    }

    public function backToDashboardProduce(Button $button, \stdClass $values): void
    {
        $this->getSession('wineIS')->set('harvestsInProduction', []);
        $this->getSession('wineIS')->set('wineProduceData', null);
        $this->redirect('dashboard');
    }

    public function harvestSelectionFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $harvestId = (int) $values->harvest;
        $harvest = $this->harvestRepository->fetchById($harvestId);
        if ($harvest === null) {
            $this->flashMessage('Sklizeň nebyla nalezena', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }

        $session = $this->getSession('wineIS');
        $harvestsInProduction = $session->get('harvestsInProduction');
        if ($harvestsInProduction === null) {
            $harvestsInProduction = [];
        }

        // Check if harvest is already in the list
        foreach ($harvestsInProduction as $existingHarvest) {
            if ($existingHarvest->getId() === $harvest->getId()) {
                $this->flashMessage('Tato sklizeň je již přidána pro výrobu vína.', FlashMsgType::ERROR->value);
                $this->redirect('this');
            }
        }

        $harvestsInProduction[] = $harvest;
        $session->set('harvestsInProduction', $harvestsInProduction);

        $this->flashMessage('Sklizeň byla úspěšně přidána pro výrobu vína.', FlashMsgType::SUCCESS->value);
        $this->redirect('this');
    }

    public function redirectToProduceP2(Button $button, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        if ($this->getSession('wineIS')->get('harvestsInProduction') === null ||
            count($this->getSession('wineIS')->get('harvestsInProduction')) === 0) {
            $this->flashMessage('Pro pokračování do kroku 2 musíte vybrat alespoň jednu sklizeň.', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }

        $this->redirect('produceP2');
    }


    public function handleRemoveHarvestFromProduction(int $harvestId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $session = $this->getSession('wineIS');
        $harvestsInProduction = $session->get('harvestsInProduction');
        if ($harvestsInProduction === null) {
            $harvestsInProduction = [];
        }

        $updatedHarvests = [];
        $found = false;
        foreach ($harvestsInProduction as $harvest) {
            if ($harvest->getId() === $harvestId) {
                $found = true;
                continue; // Skip the harvest to be deleted
            }
            $updatedHarvests[] = $harvest;
        }

        if ($found) {
            $session->set('harvestsInProduction', $updatedHarvests);
            $this->flashMessage('Sklizeň byla úspěšně odebrána z výroby vína.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Sklizeň nebyla nalezena ve výrobě vína.', FlashMsgType::ERROR->value);
        }

        $this->redirect('this');
    }

    public function renderProduceP2(): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        $harvests = $this->getSession('wineIS')->get('harvestsInProduction');
        $this->template->harvestsInProduction = $harvests ?? [];
        /** @var WineProduceData|null $wineData */
        $wineData = $this->getSession('wineIS')->get('wineProduceData');
        $form = $this->getComponent('produceWineForm');
        if ($wineData !== null) {
            $form['name']->setValue($wineData->getName());
            $form['year']->setValue($wineData->getYear());
            $form['type']->setValue($wineData->getType()->value);
            $form['color']->setValue($wineData->getColor()->value);
            $form['grade']->setValue($wineData->getGrade()->value);
            $form['volume']->setValue($wineData->getVolume());
            $form['alcoholPercentage']->setValue($wineData->getAlcoholPercentage());
            $form['leftSugar']->setValue($wineData->getLeftSugar());
            $form['acidContents']->setValue($wineData->getAcidContents());
            $form['productionNote']->setValue($wineData->getProductionNote());
        }
    }

    public function createComponentProduceWineForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Název vína')
            ->addRule(Form::MinLength, 'Název vína musí mít alespoň %d znaky.', ValidationDefinitions::NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, 'Název vína může mít maximálně %d znaků.', ValidationDefinitions::NAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vína...')
            ->setRequired('Zadejte název vína.');

        $form->addInteger('year', 'Ročník')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. ' . date('Y'))
            ->setRequired('Zadejte ročník vína.')
            ->setValue(date('Y'))
            ->addRule(Form::Min, 'Ročník nemůže být menší než %d.', ValidationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, 'Ročník nemůže být větší než aktuální rok', date('Y'));
        
        $form->addFloat('alcoholPercentage', 'Obsah alkoholu (%)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 13.5')
            ->setValue(13.5)
            ->setRequired('Zadejte obsah alkoholu ve víně.')
            ->addRule(Form::Min, 'Obsah alkoholu nemůže být menší než %d.', ValidationDefinitions::ALCOHOL_CONTENT_MIN)
            ->addRule(Form::Max, 'Obsah alkoholu nemůže být větší než %d.', ValidationDefinitions::ALCOHOL_CONTENT_MAX);

        $form->addSelect('type', 'Typ vína', [
            WineType::DRY->value => 'Suché',
            WineType::HALF_DRY->value => 'Polosuché',
            WineType::HALF_SWEET->value => 'Polosladké',
            WineType::SWEET->value => 'Sladké',
        ])
            ->setValue(WineType::DRY->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte typ vína.');

        $form->addSelect('color', 'Barva vína', [
            WineColor::WHITE->value => 'Bílé',
            WineColor::ROSE->value => 'Růžové',
            WineColor::RED->value => 'Červené',
        ])
            ->setValue(WineColor::WHITE->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte barvu vína.');
        
        $form->addSelect('grade', 'Přívlastek vína', [
            WineGrade::NONE->value => 'Bez přívlastku',
            WineGrade::CABINET->value => 'Kabinetní víno',
            WineGrade::LATE->value => 'Pozdní sběr',
            WineGrade::GRAPE_SELECTION->value => 'Výběr z hroznů',
            WineGrade::BERRY_SELECTION->value => 'Výběr z bobulí',
            WineGrade::ICE->value => 'Ledové víno',
            WineGrade::STRAW->value => 'Slámové víno',
        ])
            ->setValue(WineGrade::NONE->value)
            ->setHtmlAttribute('class', 'form-select')
            ->setRequired('Vyberte přívlastek vína.');
        
        $form->addInteger('volume', 'Objem výroby (litry)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 500')
            ->setRequired('Zadejte objem výroby vína v litrech.')
            ->addRule(Form::Min, 'Objem výroby nemůže být menší než %d litrů.', ValidationDefinitions::VOLUME_MIN)
            ->addRule(Form::Max, 'Objem výroby nemůže být větší než %d litrů.', ValidationDefinitions::VOLUME_MAX);
        
        $form->addFloat('leftSugar', 'Zbytkový cukr (g/l)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 4.5')
            ->setRequired('Zadejte hodnotu zbytkového cukru ve víně.')
            ->addRule(Form::Min, 'Zbytkový cukr nemůže být menší než %d g/l.', ValidationDefinitions::SUGAR_CONTENT_MIN)
            ->addRule(Form::Max, 'Zbytkový cukr nemůže být větší než %d g/l.', ValidationDefinitions::SUGAR_CONTENT_MAX);

        $form->addFloat('acidContents', 'Kyseliny (g/l)')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'např. 6.2')
            ->setRequired('Zadejte hodnotu kyselin ve víně.')
            ->addRule(Form::Min, 'Kyseliny nemohou být menší než %d g/l.', ValidationDefinitions::ACID_CONTENTS_MIN)
            ->addRule(Form::Max, 'Kyseliny nemohou být větší než %d g/l.', ValidationDefinitions::ACID_CONTENTS_MAX);

        $form->addTextArea('productionNote', 'Poznámka k výrobě')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', '3')
            ->setHtmlAttribute('placeholder', 'Volitelná poznámka k výrobnímu procesu...');

        $form->addSubmit('backToDashboard', 'Zrušit')
            ->setHtmlAttribute('class', 'btn btn-outline-secondary w-100 fw-bold')
            ->setValidationScope([])
            ->onClick[] = [$this, 'backToDashboardProduce'];

        $form->addSubmit('back', 'Zpět na krok 1')
            ->setHtmlAttribute('class', 'btn btn-outline-primary w-100 fw-bold')
            ->onClick[] = [$this, 'backToProduceP1'];

        $form->addSubmit('produce', 'Vyrobit víno')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold')
            ->onClick[] = [$this, 'produceWineFormSucceeded'];

        return $form;
    }

    public function backToProduceP1(Button $button, $data): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage("K výrobě vína nemáte dostatečná oprávnění.", FlashMsgType::ERROR->value);
            $this->redirect(":IS:Wine:dashboard");
            return;
        }

        /** @var WineProduceData $wineProduceData */
        $wineProduceData = new WineProduceData(
            name: isset($data->name) ? $data->name : null,
            year: isset($data->year) ? $data->year : null,
            type: WineType::from($data->type),
            color: WineColor::from($data->color),
            grade: WineGrade::from($data->grade),
            volume: isset($data->volume) ? $data->volume : null,
            alcoholPercentage: isset($data->alcoholPercentage) ? $data->alcoholPercentage : null,
            leftSugar: isset($data->leftSugar) ? $data->leftSugar : null,
            acidContents: isset($data->acidContents) ? $data->acidContents : null,
            productionNote: isset($data->productionNote) ? $data->productionNote : null
        );
        $this->getSession('wineIS')->set('wineProduceData', $wineProduceData);
        $this->redirect('produceP1');
    }

    public function produceWineFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('K výrobě vína nemáte dostatečná oprávnění.', FlashMsgType::ERROR->value);
            $this->redirect(':IS:Wine:dashboard');
        }

        $harvestsInProduction = $this->getSession('wineIS')->get('harvestsInProduction');
        if ($harvestsInProduction === null || count($harvestsInProduction) === 0) {
            $this->flashMessage('Pro výrobu vína musíte vybrat alespoň jednu sklizeň.', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }

        /** @var Wine $wine */
        $wine = new Wine(
            id: null,
            idLiquidator: null,
            idSellApprover: null,
            year: (int) $values->year,
            alcoholPercentage: (float) $values->alcoholPercentage,
            type: WineType::from($values->type),
            color: WineColor::from($values->color),
            grade: WineGrade::from($values->grade),
            state: WineState::PRODUCTION,
            volume: (int) $values->volume,
            bottlePrice: null,
            currentPrice: null,
            bottlesLeft: null,
            leftSugar: (int) $values->leftSugar,
            acidContents: (int) $values->acidContents,
            salesDescription: null,
            productionNote: (string) $values->productionNote,
            name: (string) $values->name,
            img: null,
            variety: [],
            varieties: []
        );

        $result = $this->wineRepository->produceWine($wine, $harvestsInProduction);
        if ($result) {
            // Clear session harvests
            $this->getSession('wineIS')->set('harvestsInProduction', []);
            $this->flashMessage('Víno bylo úspěšně vyrobeno.', FlashMsgType::SUCCESS->value);
            $this->redirect(':IS:Wine:dashboard');
        } else {
            $this->flashMessage('Při výrobě vína došlo k chybě.', FlashMsgType::ERROR->value);
            $this->redirect('this');
        }
    }
}
