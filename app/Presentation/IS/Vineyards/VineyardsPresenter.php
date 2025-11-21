<?php
//FILE:     VineyardsPresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for administration of vineyards
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
use App\Model\FlashMsgType;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Presenter for administration of vineyards
 * Handles vineyard and wine row management, including add, edit, delete, and search operations
 */
final class VineyardsPresenter extends BasePresenter
{

    /** @var string $patternVineyards Pattern for searching vineyards */
    private string $patternVineyards = '';
    /** @var string $patternWineRows Pattern for searching wine rows */
    private string $patternWineRows = '';
    /** @var string $typeVineyards Type filter for vineyards */
    private string $typeVineyards = 'active';
    /** @var string $typeWineRows Type filter for wine rows */
    private string $typeWineRows = 'active';

    /**
     * Constructor for VineyardsPresenter
     * @param VineyardRepository $vineyardRepository 
     * @param ImageRepository $imageRepository 
     * @param ValidationDefinitions $validationDefinitions 
     * @param WineVarietyRepository $wineVarietyRepository 
     */
    public function __construct(
        private VineyardRepository $vineyardRepository,
        private ImageRepository $imageRepository,
        private ValidationDefinitions $validationDefinitions,
        private WineVarietyRepository $wineVarietyRepository
    ) {
        parent::__construct();
    }

    /**
     * Startup method called before any action
     * Checks user role and sets template types
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
        if (!($this->user->isInRole(Role::WORKER->name) || $this->user->isInRole(Role::VINTNER->name))) {
            $this->flashMessage('Nemáte oprávnění pracovat s vinohrady.', FlashMsgType::ERROR->value);
            $this->redirect(':Home:default');
        }
        $this->template->WineVarietyType = WineVarietyType::class;
    }

    /**
     * Creates the search vineyards form component
     * @return Form Form for searching vineyards
     */
    public function createComponentSearchVineyardsForm(): Form
    {   
        /** @var Form $form Form for searching vineyards */
        $form = new Form();

        // Search bar for vineyards
        $form->addText('search', 'Hledat vinohrad')
            ->setHtmlAttribute('class', 'form-control form-control-sm')
            ->setHtmlAttribute('placeholder', 'Vyhledávejte podle názvu nebo lokace ...');

        // Select bar for type of vineyard
        $form->addSelect('type', 'Typ', [
            'all' => 'Všechny',
            'active' => 'Aktivní',
            'inactive' => 'Smazané',
        ])
            ->setDefaultValue('active')
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setHtmlAttribute('style', 'width:150px;');

        // Submit button for search form
        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchVineyardsFormSucceeded'];

        return $form;
    }

    /**
     * Callback for successful search vineyards form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Search values
     * @return void
     */
    public function searchVineyardsFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternVineyards = $values->search;
        $this->typeVineyards = $values->type;
    }

    /**
     * Renders the vineyards dashboard
     * @return void
     */
    public function renderDashboard(): void
    {
        /** @var Vineyard[] $vineyards Fetch all vineyards with pattern and type */
        $vineyards = $this->vineyardRepository->fetchAllWithPattern($this->patternVineyards, $this->typeVineyards);
        /** @var Vineyard $vineyard Each vineyard in the list */
        foreach ($vineyards as $vineyard) {
            $vineyardId = $vineyard->getId();
            $wineRowsCount = count($this->vineyardRepository->getWineRowsOnVineyard($vineyardId));
            $vineyard->setWineRowsCount($wineRowsCount);
            $wineVarietiesCount = count($this->vineyardRepository->getUniqueWineVarietiesOnVineyard($vineyardId));
            $vineyard->setWineVarietiesCount($wineVarietiesCount);
        }
        // Give all vineyard and images to the template
        $this->template->vineyards = $vineyards;
        $this->template->imageRepository = $this->imageRepository;
    }

    /**
     * Handles deletion of a vineyard
     * @param int $vineyardId ID of the vineyard to delete
     * @return void
     */
    public function handleDelete(int $vineyardId): void
    {
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění smazat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        
        // Deactivate vineyard
        if ($this->vineyardRepository->setVineyardActiveStatus($vineyardId, false)) {
            $this->flashMessage('Vinohrad byl úspěšně smazán.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Vinohrad se nepodařilo smazat.', FlashMsgType::ERROR->value);
        }
    }

    /**
     * Handles reactivation of a vineyard
     * @param int $vineyardId ID of the vineyard to reactivate
     * @return void
     */
    public function handleReactivate(int $vineyardId): void
    {
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění obnovit vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        
        // Reactivate the vineyard
        if ($this->vineyardRepository->setVineyardActiveStatus($vineyardId, true)) {
            $this->flashMessage('Vinohrad byl úspěšně obnoven.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Vinohrad se nepodařilo obnovit.', FlashMsgType::ERROR->value);
        }
    }

    /**
     * Renders the add vineyard form
     * @return void
     */
    public function renderAdd(): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění přidat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
    }

    /**
     * Creates the add vineyard form component
     * @return Form Form for adding a new vineyard
     */
    public function createComponentAddVineyardForm(): Form
    {
        /** @var Form $form Form for adding a new vineyard */
        $form = new Form();
        
        // Input field for name of vineyard
        $form->addText('name', 'Název vinohradu')
            ->addRule(Form::MinLength, "Název vinohradu musí mít alespoň %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, "Název vinohradu může mít maximálně %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vinohradu');
        // Input field for location of vineyard
        $form->addText('location', 'Lokace')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');
        // Input field for description of vineyard
        $form->addTextArea('description', 'Detailní popis')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');
        // Input field for year established of vineyard
        $form->addInteger('year', 'Rok založení')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setDefaultValue(date('Y'))
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');
        // Upload field for vineyard image
        $form->addUpload('image', 'Obrázek')
            ->addRule($form::Image, "Nahrajte prosím platný obrázek.")
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek vinohradu');
        // Submit button for adding vineyard
        $form->addSubmit('send', 'Přidat vinohrad')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');

        $form->onSuccess[] = [$this, 'addVineyardFormSucceeded'];

        return $form;
    }

    /**
     * Callback for successful add vineyard form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Vineyard values
     * @return void
     */
    public function addVineyardFormSucceeded(Form $form, \stdClass $values): void
    {   
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění přidat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        /** @var FileUpload $image Uploaded image from form */
        $image = $values->image;
        $imagePath = null;
        /** @var Image|null $imageEntity */
        $imageEntity = null;
        // Handle image upload
        if ($image->hasFile()) {
            $imagePath = $this->imageRepository->uploadImage($values->image);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $form->addError('Obrázek se nepodařilo nahrát.');
                return;
            }
            $imageEntity = new Image(null, $imagePath, $values->name, 'Obrázek vinohradu ' . $values->name);
        }

        // Handle description
        $description = null;
        if ($values->description !== null && strlen($values->description) > 0) {
            $description = $values->description;
        }

        /** @var Vineyard $vineyard Created vineyard entity. */
        $vineyard = new Vineyard(
            null,
            true,
            $values->name,
            $values->location,
            $imageEntity,
            $description,
            $values->year
        );

        /** @var bool $result Flag of the add operation of vineyard.*/
        $result = $this->vineyardRepository->addVineyard($vineyard);

        // Check the result
        if ($result) {
            $this->flashMessage('Vinohrad byl úspěšně přidán.', FlashMsgType::SUCCESS->value);
            $this->redirect('dashboard');
        } else {
            $form->addError('Vinohrad se nepodařilo přidat.');
            $this->flashMessage('Vinohrad se nepodařilo přidat.', FlashMsgType::ERROR->value);
        }
    }

    /**
     * Renders the edit vineyard form
     * @param int $vineyardId ID of the vineyard to edit
     * @return void
     */
    public function renderEdit(int $vineyardId): void
    {
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('show', $vineyardId);
        }

        /** @var Vineyard|null $vineyard Fetched vineyard for edit */
        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        // Check if vineyard exists
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        // Check if vineyard is active
        if (!$vineyard->isActive()) {
            $this->flashMessage('Tento vinohrad je smazaný a nelze s ním pracovat.', FlashMsgType::ERROR->value);
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

    /**
     * Creates the edit vineyard form component
     * @return Form Form for editing a vineyard
     */
    public function createComponentEditVineyardForm(): Form
    {   
        /** @var Form $form Form for editing a vineyard */
        $form = new Form();

        // Input field for name of vineyard
        $form->addText('name', 'Název vinohradu')
            ->addRule(Form::MinLength, "Název vinohradu musí mít alespoň %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MIN)
            ->addRule(Form::MaxLength, "Název vinohradu může mít maximálně %d znaků.", $this->validationDefinitions::VINEYARD_NAME_LENGTH_MAX)
            ->setRequired('Zadejte název vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název vinohradu');

        // Input field for location of vineyard
        $form->addText('location', 'Lokace')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');

        // Input field for description of vineyard
        $form->addTextArea('description', 'Detailní popis')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');

        // Input field for year established of vineyard
        $form->addInteger('year', 'Rok založení')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');

        // Upload field for vineyard image 
        $form->addUpload('image', 'Obrázek')
            ->addRule($form::Image, "Nahrajte prosím platný obrázek.")
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek vinohradu');

        // Submit button for editing vineyard
        $form->addSubmit('send', 'Upravit vinohrad')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');
        
        $form->onSuccess[] = [$this, 'editVineyardFormSucceeded'];

        return $form;
    }

    /**
     * Callback for successful edit vineyard form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Vineyard values
     * @return void
     */
    public function editVineyardFormSucceeded(Form $form, \stdClass $values): void
    {
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('show', $this->getParameter('vineyardId'));
        }

        /** @var int $vineyardId ID of actual vineyard which will be edited. */
        $vineyardId = (int)$this->getParameter('vineyardId');
        
        /** @var Vineyard|null $vineyard Fetched vineyard for edit. */
        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        // Check if vineyard is active
        if (!$vineyard->isActive()) {
            $this->flashMessage('Tento vinohrad je smazaný a nelze s ním pracovat.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        /** @var FileUpload $image Uploaded new image.*/
        $image = $values->image;
        // Keep existing image by default
        $imageEntity = $vineyard->getImage(); 
        
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
        // Handle description
        $description = null;
        if ($values->description !== null && strlen($values->description) > 0) {
            $description = $values->description;
        }

        /** @var Vineyard $updatedVineyard Updated vineyard entity with edited values. */
        $updatedVineyard = new Vineyard(
            $vineyardId,
            $vineyard->isActive(),
            $values->name,
            $values->location,
            $imageEntity,
            $description,
            $values->year
        );

        /** @var bool $result Result of the update operation. */
        $result = $this->vineyardRepository->updateVineyard($updatedVineyard);

        // Check the result of editing
        if ($result) {
            $this->flashMessage('Vinohrad byl úspěšně upraven.', FlashMsgType::SUCCESS->value);
            $this->redirect('show', $vineyardId);
        } else {
            $form->addError('Vinohrad se nepodařilo upravit.');
            $this->flashMessage('Vinohrad se nepodařilo upravit.', FlashMsgType::ERROR->value);
        }
    }

    /**
     * Handles removal of a vineyard image
     * @param int $id ID of the vineyard
     * @return void
     */
    public function handleRemoveImage(int $id): void
    {
        // Check the role for this action
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění upravovat vinohrad.', FlashMsgType::ERROR->value);
            $this->redirect('show', $id);
        }

        /** @var Vineyard $vineyard Vineyard entity what will be edited */
        $vineyard = $this->vineyardRepository->fetchById($id);
        // Check if vineyard exists
        if ($vineyard === null) {
            $this->flashMessage('Vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        // Check if vineyard is active
        if (!$vineyard->isActive()) {
            $this->flashMessage('Tento vinohrad je smazaný a nelze s ním pracovat.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        // Check if vineyard has an image
        if ($vineyard->getImage() === null) {
            $this->flashMessage('Vinohrad nemá žádný obrázek.', FlashMsgType::ERROR->value);
            $this->redirect('edit', $id);
        }

        // Remove image from vineyard
        $updatedVineyard = new Vineyard(
            $id,
            $vineyard->isActive(),
            $vineyard->getName(),
            $vineyard->getLocation(),
            null, // Remove image
            $vineyard->getDescription(),
            $vineyard->getYearEstablished()
        );

        /** @var bool $result Result of the update operation, image removal. */
        $result = $this->vineyardRepository->updateVineyard($updatedVineyard);
        // Check the result of image removal
        if ($result) {
            $this->flashMessage('Obrázek byl úspěšně odstraněn.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Obrázek se nepodařilo odstranit.', FlashMsgType::ERROR->value);
        }

        $this->redirect('edit', $id);
    }

    /**
     * Renders the vineyard detail.
     * @param int $vineyardId ID of the vineyard to show
     * @return void
     */
    public function renderShow(int $vineyardId): void
    {
        /** @var Vineyard $vineyard Vineyard entity to show */
        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }
        // Check if vineyard is active
        if (!$vineyard->isActive()) {
            $this->flashMessage('Tento vinohrad je smazaný a nelze s ním pracovat.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        /** @var WineRow[] $wineRows Rows of the vineyard */
        $wineRows = $this->vineyardRepository->getWineRowsOnVineyardWithPattern($vineyardId, $this->patternWineRows, $this->typeWineRows);
        /** @var  WineVariety[] $wineVarieties Wine varieties on the vineyard */
        $wineVarieties = $this->vineyardRepository->getUniqueWineVarietiesOnVineyard($vineyardId);
        // Set count of the wine rows and wine varieties to the vineyard
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

        // Give data to template
        $this->template->vineyard = $vineyard;
        $this->template->wineVarieties = $wineVarieties;
        $this->template->wineRows = $wineRows;
        $this->template->wineVarietiesHeadCount = $wineVarietiesHeadCount;
        $this->template->imageRepository = $this->imageRepository;
    }


    /**
     * Creates the add wine row form component
     * @return Form Form for adding a wine row
     */
    public function createComponentAddWineRowForm(): Form
    {
        /** @var Form  Form for add wine row to vineyard. */
        $form = new Form;

        // Input field for row number
        $form->addInteger("rowNumber", "Číslo řádku")
            ->setRequired('Zadejte číslo řádku.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte číslo řádku');

        // Input field for year of planting
        $form->addInteger("year", "Rok výsadby")
            ->setDefaultValue(date('Y'))
            ->addRule(Form::Min, "Rok musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok výsadby.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok výsadby');

        // Input field for head count
        $form->addInteger('headCount', 'Počet keřů')
            ->addRule(Form::Min, "Počet keřů musí být alespoň %d.", $this->validationDefinitions::HEAD_COUNT_MIN)
            ->addRule(Form::Max, "Počet keřů nesmí být větší než %d.", $this->validationDefinitions::HEAD_COUNT_MAX)
            ->setRequired('Zadejte počet keřů.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte počet keřů');

        /** @var WineVariety[] $wineVarieties Wine varieties of the vineyard */
        $wineVarieties = $this->wineVarietyRepository->fetchAll();
        /** @var array<string, string> $selectItems Selected wine varieties with code of the row*/
        $selectItems = [];
        foreach ($wineVarieties as $variety) {
            $selectItems[$variety->getId()] = $variety->getName();
        }

        // Select field for wine variety
        $form->addSelect('variety', 'Odrůda vína')
            ->setItems($selectItems)
            ->setRequired('Vyberte odrůdu vína.')
            ->setHtmlAttribute('class', 'form-control');
        // Submit button for adding wine row
        $form->addSubmit('save', 'Přidat řádek')
            ->setHtmlAttribute('class', 'btn btn-primary text-white w-100 fw-bold');

        $form->onSuccess[] = [$this, 'addWineRowFormSucceeded'];

        return $form;
    }

    /**
     * Callback for successful add wine row form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Wine row values
     * @return void
     */
    public function addWineRowFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show ', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');

        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Vinohrad pro řádek nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        // check with row id exists
        $wineRow = $this->vineyardRepository->getWineRowById($vineyardId, $values->rowNumber);
        if ($wineRow !== null) {
            $form->addError('Řádek s tímto číslem již existuje.');
            $this->flashMessage('Řádek s tímto číslem již existuje.', FlashMsgType::ERROR->value);
            return;
        }

        /** @var WineRow $wineRow */
        $wineRow = new WineRow(
            $values->rowNumber,
            true,
            $vineyardId,
            $values->variety,
            $values->headCount,
            $values->year
        );

        $result = $this->vineyardRepository->addWineRowToVineyard($wineRow);
        if ($result) {
            $this->flashMessage('Řádek byl úspěšně přidán.', FlashMsgType::SUCCESS->value);
            $this->redirect('show', $vineyardId);
        } else {
            $form->addError('Řádek se nepodařilo přidat.');
            $this->flashMessage('Řádek se nepodařilo přidat.', FlashMsgType::ERROR->value);
        }
    }

    /**
     * Creates the edit wine row form component
     * @return Form Form for editing a wine row
     */
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

    /**
     * Callback for successful edit wine row form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Wine row values
     * @return void
     */
    public function editWineRowFormSucceeded(Form $form, \stdClass $values): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show ', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');
        $rowId = (int)$this->getParameter('rowId');

        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Vinohrad pro řádek nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        // check with row id exists
        $existingWineRow = $this->vineyardRepository->getWineRowById($vineyardId, $rowId);
        if ($existingWineRow === null) {
            $form->addError('Řádek s tímto číslem neexistuje.');
            $this->flashMessage('Řádek s tímto číslem neexistuje.', FlashMsgType::ERROR->value);
            $this->redirect('show', $vineyardId);
        }

        /** @var WineRow $wineRow */
        $wineRow = new WineRow(
            $rowId,
            true,
            $vineyardId,
            $values->variety,
            $values->headCount,
            $values->year
        );

        $result = $this->vineyardRepository->updateWineRowOnVineyard($wineRow);
        if ($result) {
            $this->flashMessage('Řádek byl úspěšně upraven.', FlashMsgType::SUCCESS->value);
            $this->redirect('show', $vineyardId);
        } else {
            $form->addError('Řádek se nepodařilo upravit.');
            $this->flashMessage('Řádek se nepodařilo upravit.', FlashMsgType::ERROR->value);
        }
    }


    /**
     * Renders the edit wine row form
     * @param int $vineyardId ID of the vineyard
     * @param int $rowId ID of the wine row to edit
     * @return void
     */
    public function renderEditWineRow(int $vineyardId, int $rowId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show', $vineyardId);
        }

        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
            $this->redirect('dashboard');
        }

        // Get wine row for this vineyard
        $wineRow = $this->vineyardRepository->getWineRowById($vineyardId, $rowId);
        if ($wineRow === null) {
            Debugger::log("Wine row with ID $rowId not found in vineyard $vineyardId", ILogger::ERROR);
            $this->flashMessage('Požadovaný řádek nebyl nalezen.', FlashMsgType::ERROR->value);
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

    /**
     * Renders the add wine row form
     * @param int $vineyardId ID of the vineyard
     * @return void
     */
    public function renderAddWineRow(int $vineyardId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show', $vineyardId);
        }

        $vineyard = $this->vineyardRepository->fetchById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', FlashMsgType::ERROR->value);
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
            $this->flashMessage('Nejdříve je potřeba přidat nějaké odrůdy vína.', FlashMsgType::ERROR->value);
            $this->redirect(':IS:WineVarieties:dashboard');
        }

        $form = $this->getComponent('addWineRowForm');
        $form['rowNumber']->setValue($firstAvailableRowNumber);

        $this->template->vineyard = $vineyard;
        $this->template->firstAvailableRowNumber = $firstAvailableRowNumber;
    }

    /**
     * Handles deletion of a wine row
     * @param int $rowId ID of the wine row to delete
     * @return void
     */
    public function handleDeleteWineRow(int $rowId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');

        if ($this->vineyardRepository->setWineRowActiveStatus($vineyardId, $rowId, false)) {
            $this->flashMessage('Řádek byl úspěšně smazán.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Řádek se nepodařilo smazat.', FlashMsgType::ERROR->value);
        }

        $this->redirect('show', $vineyardId);
    }

    /**
     * Handles reactivation of a wine row
     * @param int $rowId ID of the wine row to reactivate
     * @return void
     */
    public function handleReactivateWineRow(int $rowId): void
    {
        if (!$this->user->isInRole(Role::VINTNER->name)) {
            $this->flashMessage('Nemáte oprávnění pracovat s řádky vinohradu.', FlashMsgType::ERROR->value);
            $this->redirect('show', $this->getParameter('vineyardId'));
        }

        $vineyardId = (int)$this->getParameter('vineyardId');

        if ($this->vineyardRepository->setWineRowActiveStatus($vineyardId, $rowId, true)) {
            $this->flashMessage('Řádek byl úspěšně obnoven.', FlashMsgType::SUCCESS->value);
        } else {
            $this->flashMessage('Řádek se nepodařilo obnovit.', FlashMsgType::ERROR->value);
        }

        $this->redirect('show', $vineyardId);
    }

    /**
     * Creates the search wine rows form component
     * @return Form Form for searching wine rows
     */
    public function createComponentSearchWineRowsForm(): Form
    {
        $form = new Form();

        $form->addText('search', 'Hledat řádky')
            ->setHtmlAttribute('class', 'form-control form-control-sm')
            ->setHtmlAttribute('placeholder', 'Vyhledávejte podle čísla řádku, roku nebo odrůdy ...');

        $form->addSelect('type', 'Typ', [
            'all' => 'Všechny',
            'active' => 'Aktivní',
            'inactive' => 'Smazané',
        ])
            ->setDefaultValue('active')
            ->setHtmlAttribute('class', 'form-select form-select-sm')
            ->setHtmlAttribute('style', 'width:150px;');

        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchWineRowsFormSucceeded'];

        return $form;
    }

    /**
     * Callback for successful search wine rows form submission
     * @param Form $form The submitted form
     * @param \stdClass $values Search values
     * @return void
     */
    public function searchWineRowsFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternWineRows = $values->search;
        $this->typeWineRows = $values->type;
    }
}
