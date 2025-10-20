<?php

declare(strict_types=1);

namespace App\Presentation\IS\Vineyards;

use App\Model\Image\Image;
use App\Model\Image\ImageRepository;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Validation\ValidationDefinitions;
use App\Model\Vineyard\VineyardRepository;
use App\Model\Vineyard\Vineyard;
use App\Model\WineRow\WineRow;
use App\Model\WineVariety\WineVariety;
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\WineVariety\WineVarietyType;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

final class VineyardsPresenter extends BasePresenter
{

    /** @var string $patternVineyards */
    private string $patternVineyards = '';
    /** @var string $patternWineRows */
    private string $patternWineRows = '';

    public function __construct(
        private VineyardRepository $vineyardRepository,
        private ImageRepository $imageRepository,
        private ValidationDefinitions $validationDefinitions,
        private WineVarietyRepository $wineVarietyRepository
    ) {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->redirect(':Home:default');
        }
        $this->template->WineVarietyType = WineVarietyType::class;
    }

    public function createComponentSearchVineyardsForm(): Form
    {
        $form = new Form();

        $form->addText('search', 'Hledat vinohrad')
            ->setHtmlAttribute('class', 'form-control form-control-sm')
            ->setHtmlAttribute('placeholder', 'Vyhledávejte podle názvu nebo lokace ...');

        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchVineyardsFormSucceeded'];

        return $form;
    }

    public function searchVineyardsFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternVineyards = $values->search;
    }

    public function renderDashboard(): void
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = $this->vineyardRepository->fetchAllWithPattern($this->patternVineyards);
        foreach ($vineyards as $vineyard) {
            /** @var Vineyard $vineyard */
            $vineyardId = $vineyard->getId();
            $wineRowsCount = count($this->vineyardRepository->getWineRowsOnVineyard($vineyardId));
            $vineyard->setWineRowsCount($wineRowsCount);
            $wineVarietiesCount = count($this->vineyardRepository->getUniqueWineVarietiesOnVineyard($vineyardId));
            $vineyard->setWineVarietiesCount($wineVarietiesCount);
        }
        
        $this->template->vineyards = $vineyards;
        $this->template->imageRepository = $this->imageRepository;
    }

    public function handleDelete(int $vineyardId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění smazat vinohrad.', 'error');
            $this->redirect('dashboard');
        }

        if ($this->vineyardRepository->deleteVineyard($vineyardId)) {
            $this->flashMessage('Vinohrad byl úspěšně smazán.', 'success');
        } else {
            $this->flashMessage('Vinohrad se nepodařilo smazat.', 'error');
        }
        
    }

    public function renderAdd(): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění přidat vinohrad.', 'error');
            $this->redirect('dashboard');
        }
    }

    public function createComponentAddVineyardForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Název vinohradu')
            ->addRule(Form::MinLength, "Název vinohradu musí mít alespoň %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, "Název vinohradu může mít maximálně %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vinohradu');

        $form->addText('location', 'Lokace')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');

        $form->addTextArea('description', 'Detailní popis')
            ->setRequired('Zadejte popis vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');

        $form->addInteger('year', 'Rok založení')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setDefaultValue(date('Y'))
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');

        $form->addUpload('image', 'Obrázek')
            ->addRule($form::Image, "Nahrajte prosím platný obrázek.")
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek vinohradu');

        $form->addSubmit('send', 'Přidat vinohrad')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');

        $form->onSuccess[] = [$this, 'addVineyardFormSucceeded'];

        return $form;
    }

    public function addVineyardFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění přidat vinohrad.', 'error');
            $this->redirect('dashboard');
        }

        /** @var FileUpload $image */
        $image = $values->image;
        $imagePath = null;
        /** @var ?Image $imageEntity */
        $imageEntity = null;
        if ($image->hasFile()) {
            $imagePath = $this->imageRepository->uploadImage($values->image);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $form->addError('Obrázek se nepodařilo nahrát.');
                return;
            }
            $imageEntity = new Image(null, $imagePath, $values->name, 'Obrázek vinohradu ' . $values->name);
        }

        $vineyard = new Vineyard(
            null,
            $values->name,
            $values->location,
            $imageEntity,
            $values->description,
            $values->year
        );

        /** @var bool $result */
        $result = $this->vineyardRepository->addVineyard($vineyard);

        if ($result) {
            $this->flashMessage('Vinohrad byl úspěšně přidán.', 'success');
            $this->redirect('dashboard');
        } else {
            $form->addError('Vinohrad se nepodařilo přidat.');
            $this->flashMessage('Vinohrad se nepodařilo přidat.', 'error');
        }
    }

    

    public function renderEdit(int $vineyardId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', 'error');
            $this->redirect('show', $vineyardId);
        }

        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // Populate form with current values
        $form = $this->getComponent('editVineyardForm');
        $form['name']->setValue($vineyard->getName());
        $form['location']->setValue($vineyard->getLocation());
        $form['description']->setValue($vineyard->getDescription());
        $form['year']->setValue($vineyard->getYearEstablished());

        $this->template->vineyard = $vineyard;
        $this->template->imageRepository = $this->imageRepository;
    }

    public function createComponentEditVineyardForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Název vinohradu')
            ->addRule(Form::MinLength, "Název vinohradu musí mít alespoň %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, "Název vinohradu může mít maximálně %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vinohradu');

        $form->addText('location', 'Lokace')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');

        $form->addTextArea('description', 'Detailní popis')
            ->setRequired('Zadejte popis vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');

        $form->addInteger('year', 'Rok založení')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');

        $form->addUpload('image', 'Obrázek')
            ->addRule($form::Image, "Nahrajte prosím platný obrázek.")
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek vinohradu');

        $form->addSubmit('send', 'Upravit vinohrad')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');
        
        $form->onSuccess[] = [$this, 'editVineyardFormSucceeded'];

        return $form;
    }

    public function editVineyardFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', 'error');
            $this->redirect('show', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');
        
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        /** @var FileUpload $image */
        $image = $values->image;
        $imageEntity = $vineyard->getImage(); // Keep existing image by default
        
        // Handle new image upload
        if ($image->hasFile()) {
            $imagePath = $this->imageRepository->uploadImage($image);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $form->addError('Obrázek se nepodařilo nahrát.');
                return;
            }
            $imageEntity = new Image(null, $imagePath, $values->name, 'Obrázek vinohradu ' . $values->name);
        }

        // Update vineyard with new values
        $updatedVineyard = new Vineyard(
            $vineyardId,
            $values->name,
            $values->location,
            $imageEntity,
            $values->description,
            $values->year
        );

        $result = $this->vineyardRepository->updateVineyard($updatedVineyard);

        if ($result) {
            $this->flashMessage('Vinohrad byl úspěšně upraven.', 'success');
            $this->redirect('edit', $vineyardId);
        } else {
            $form->addError('Vinohrad se nepodařilo upravit.');
            $this->flashMessage('Vinohrad se nepodařilo upravit.', 'error');
        }
    }

    public function handleRemoveImage(int $id): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', 'error');
            $this->redirect('show', $id);
        }

        $vineyard = $this->vineyardRepository->getVineyardById($id);
        if ($vineyard === null) {
            $this->flashMessage('Vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        if ($vineyard->getImage() === null) {
            $this->flashMessage('Vinohrad nemá žádný obrázek.', 'error');
            $this->redirect('edit', $id);
        }

        // Remove image from vineyard
        $updatedVineyard = new Vineyard(
            $id,
            $vineyard->getName(),
            $vineyard->getLocation(),
            null, // Remove image
            $vineyard->getDescription(),
            $vineyard->getYearEstablished()
        );

        $result = $this->vineyardRepository->updateVineyard($updatedVineyard);

        if ($result) {
            $this->flashMessage('Obrázek byl úspěšně odstraněn.', 'success');
        } else {
            $this->flashMessage('Obrázek se nepodařilo odstranit.', 'error');
        }

        $this->redirect('edit', $id);
    }

    public function renderShow(int $vineyardId): void
    {
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // Get wine rows and varieties for this vineyard
        /** @var WineRow[] $wineRows */
        $wineRows = $this->vineyardRepository->getWineRowsOnVineyardWithPattern($vineyardId, $this->patternWineRows);
        $wineVarieties = $this->vineyardRepository->getUniqueWineVarietiesOnVineyard($vineyardId);
        $vineyard->setWineRowsCount(count($wineRows));
        $vineyard->setWineVarietiesCount(count($wineVarieties));

        // Get total head count for each wine variety on this vineyard
        $wineVarietiesHeadCount = [];
        foreach ($wineVarieties as $variety) {
            /** @var WineVariety $variety */
            $totalHeadCount = $this->vineyardRepository->getWineVarietyTotalHeadCountOnVineyard($vineyardId, $variety->getId());
            $wineVarietiesHeadCount[$variety->getId()] = $totalHeadCount;
        }

        // Get total head count on vineyard and setup wine variety for each row
        $totalHeadCount = 0;
        foreach ($wineRows as $row) {
            /** @var WineRow $row */
            $totalHeadCount += $row->getHeadCount();
            $row->setWineVariety($this->vineyardRepository->getWineVarietyForWineRow($vineyardId, $row->getId()));
        }

        $vineyard->setTotalHeadCount($totalHeadCount);

        $this->template->vineyard = $vineyard;
        $this->template->wineVarieties = $wineVarieties;
        $this->template->wineRows = $wineRows;
        $this->template->wineVarietiesHeadCount = $wineVarietiesHeadCount;
        $this->template->imageRepository = $this->imageRepository;
    }


    public function createComponentAddWineRowForm(): Form
    {
        $form = new Form;

        $form->addInteger("rowNumber", "Číslo řádku")
            ->setRequired('Zadejte číslo řádku.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte číslo řádku');

        $form->addInteger("year", "Rok výsadby")
            ->setDefaultValue(date('Y'))
            ->addRule(Form::Min, "Rok musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok výsadby.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok výsadby');

        $form->addInteger('headCount', 'Počet keřů')
            ->addRule(Form::Min, "Počet keřů musí být alespoň %d.", $this->validationDefinitions::HEAD_COUNT_MIN)
            ->addRule(Form::Max, "Počet keřů nesmí být větší než %d.", $this->validationDefinitions::HEAD_COUNT_MAX)
            ->setRequired('Zadejte počet keřů.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte počet keřů');

        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = $this->wineVarietyRepository->fetchAll();
        $selectItems = [];
        foreach ($wineVarieties as $variety) {
            $selectItems[$variety->getId()] = $variety->getName();
        }

        $form->addSelect('variety', 'Odrůda vína')
            ->setItems($selectItems)
            ->setRequired('Vyberte odrůdu vína.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('save', 'Přidat řádek')
            ->setHtmlAttribute('class', 'btn btn-primary text-white w-100 fw-bold');

        $form->onSuccess[] = [$this, 'addWineRowFormSucceeded'];

        return $form;
    }

    public function addWineRowFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', 'error');
            $this->redirect('show ', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');

        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Vinohrad pro řádek nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // check with row id exists
        $wineRow = $this->vineyardRepository->getWineRowById($vineyardId, $values->rowNumber);
        if ($wineRow !== null) {
            $form->addError('Řádek s tímto číslem již existuje.');
            $this->flashMessage('Řádek s tímto číslem již existuje.', 'error');
            return;
        }

        /** @var WineRow $wineRow */
        $wineRow = new WineRow(
            $values->rowNumber,
            $vineyardId,
            $values->variety,
            $values->headCount,
            $values->year
        );

        $result = $this->vineyardRepository->addWineRowToVineyard($wineRow);
        if ($result) {
            $this->flashMessage('Řádek byl úspěšně přidán.', 'success');
            $this->redirect('show', $vineyardId);
        } else {
            $form->addError('Řádek se nepodařilo přidat.');
            $this->flashMessage('Řádek se nepodařilo přidat.', 'error');
        }
    }

    public function createComponentEditWineRowForm(): Form
    {
        $form = new Form;

        $form->addInteger("rowNumber", "Číslo řádku")
            ->setDisabled(true)
            ->setHtmlAttribute('class', 'form-control');

        $form->addInteger("year", "Rok výsadby")
            ->addRule(Form::Min, "Rok musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok výsadby.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok výsadby');

        $form->addInteger('headCount', 'Počet keřů')
            ->addRule(Form::Min, "Počet keřů musí být alespoň %d.", $this->validationDefinitions::HEAD_COUNT_MIN)
            ->addRule(Form::Max, "Počet keřů nesmí být větší než %d.", $this->validationDefinitions::HEAD_COUNT_MAX)
            ->setRequired('Zadejte počet keřů.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte počet keřů');

        /** @var WineVariety[] $wineVarieties */
        $wineVarieties = $this->wineVarietyRepository->fetchAll();
        $selectItems = [];
        foreach ($wineVarieties as $variety) {
            $selectItems[$variety->getId()] = $variety->getName();
        }

        $form->addSelect('variety', 'Odrůda vína')
            ->setItems($selectItems)
            ->setRequired('Vyberte odrůdu vína.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('save', 'Upravit řádek')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold text-white');

        $form->onSuccess[] = [$this, 'editWineRowFormSucceeded'];

        return $form;
    }

    public function editWineRowFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', 'error');
            $this->redirect('show ', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');
        $rowId = (int)$this->getParameter('rowId');

        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Vinohrad pro řádek nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // check with row id exists
        $existingWineRow = $this->vineyardRepository->getWineRowById($vineyardId, $rowId);
        if ($existingWineRow === null) {
            $form->addError('Řádek s tímto číslem neexistuje.');
            $this->flashMessage('Řádek s tímto číslem neexistuje.', 'error');
            $this->redirect('show', $vineyardId);
        }

        /** @var WineRow $wineRow */
        $wineRow = new WineRow(
            $rowId,
            $vineyardId,
            $values->variety,
            $values->headCount,
            $values->year
        );

        $result = $this->vineyardRepository->updateWineRowOnVineyard($wineRow);
        if ($result) {
            $this->flashMessage('Řádek byl úspěšně upraven.', 'success');
            $this->redirect('show', $vineyardId);
        } else {
            $form->addError('Řádek se nepodařilo upravit.');
            $this->flashMessage('Řádek se nepodařilo upravit.', 'error');
        }
    }


    public function renderEditWineRow(int $vineyardId, int $rowId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', 'error');
            $this->redirect('show', $vineyardId);
        }

        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // Get wine row for this vineyard
        $wineRow = $this->vineyardRepository->getWineRowById($vineyardId, $rowId);
        if ($wineRow === null) {
            Debugger::log("Wine row with ID $rowId not found in vineyard $vineyardId", ILogger::ERROR);
            $this->flashMessage('Požadovaný řádek nebyl nalezen.', 'error');
            $this->redirect('show', $vineyardId);
        }

        // Populate form with current values
        $form = $this->getComponent('editWineRowForm');
        $form['rowNumber']->setValue($wineRow->getId());
        $form['year']->setValue($wineRow->getYearPlanted());
        $form['headCount']->setValue($wineRow->getHeadCount());
        $form['variety']->setValue($wineRow->getWineVarietyId());

        $this->template->vineyard = $vineyard;
        $this->template->wineRow = $wineRow;
    }

    public function renderAddWineRow(int $vineyardId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', 'error');
            $this->redirect('show', $vineyardId);
        }

        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'error');
            $this->redirect('dashboard');
        }

        // get wine rows on this vineyard to obtain first available row number
        /** @var WineRow[] $wineRows */
        $wineRows = $this->vineyardRepository->getWineRowsOnVineyard($vineyardId);
        $usedRowNumbers = [];
        foreach ($wineRows as $row) {
            /** @var WineRow $row */
            $usedRowNumbers[] = $row->getId();
        }
        $firstAvailableRowNumber = 1;
        while (in_array($firstAvailableRowNumber, $usedRowNumbers, true)) {
            $firstAvailableRowNumber++;
        }

        if (count($this->wineVarietyRepository->fetchAll()) === 0) {
            $this->flashMessage('Nejdříve je potřeba přidat nějaké odrůdy vína.', 'error');
            $this->redirect(':IS:WineVarieties:dashboard');
        }

        $form = $this->getComponent('addWineRowForm');
        $form['rowNumber']->setValue($firstAvailableRowNumber);

        $this->template->vineyard = $vineyard;
        $this->template->firstAvailableRowNumber = $firstAvailableRowNumber;
    }

    public function handleDeleteWineRow(int $rowId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', 'error');
            $this->redirect('show', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');

        if ($this->vineyardRepository->deleteWineRowFromVineyard($vineyardId, $rowId)) {
            $this->flashMessage('Řádek byl úspěšně smazán.', 'success');
        } else {
            $this->flashMessage('Řádek se nepodařilo smazat.', 'error');
        }

        $this->redirect('show', $vineyardId);
    }

    public function createComponentSearchWineRowsForm(): Form
    {
        $form = new Form();

        $form->addText('search', 'Hledat řádky')
            ->setHtmlAttribute('class', 'form-control form-control-sm')
            ->setHtmlAttribute('placeholder', 'Vyhledávejte podle čísla řádku, roku nebo odrůdy ...');

        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchWineRowsFormSucceeded'];

        return $form;
    }

    public function searchWineRowsFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternWineRows = $values->search;
    }
}
