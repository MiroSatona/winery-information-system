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
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\WineVariety\WineVarietyType;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

final class VineyardsPresenter extends BasePresenter
{

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

    public function renderDashboard(): void
    {
        /** @var Vineyard[] $vineyards */
        $vineyards = $this->vineyardRepository->fetchAll();
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
        if ($this->vineyardRepository->deleteVineyard($vineyardId)) {
            $this->flashMessage('Vinohrad byl úspěšně smazán.', 'success');
        } else {
            $this->flashMessage('Vinohrad se nepodařilo smazat.', 'danger');
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

        $form->addText('location', 'Lokace vinohradu')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');

        $form->addTextArea('description', 'Popis vinohradu')
            ->setRequired('Zadejte popis vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');

        $form->addInteger('year', 'Rok založení vinohradu')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setDefaultValue(date('Y'))
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');

        $form->addUpload('image', 'Obrázek vinohradu')
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
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'danger');
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

        $form->addText('location', 'Lokace vinohradu')
            ->setRequired('Zadejte lokaci vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte lokaci vinohradu');

        $form->addTextArea('description', 'Popis vinohradu')
            ->setRequired('Zadejte popis vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis vinohradu');

        $form->addInteger('year', 'Rok založení vinohradu')
            ->addRule(Form::Min, "Rok založení vinohradu musí být alespoň %d.", $this->validationDefinitions::YEAR_MIN)
            ->addRule(Form::Max, "Rok založení vinohradu nesmí být větší než %d.", $this->validationDefinitions::YEAR_MAX)
            ->setRequired('Zadejte rok založení vinohradu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte rok založení vinohradu');

        $form->addUpload('image', 'Obrázek vinohradu')
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
        $vineyardId = (int)$this->getParameter('vineyardId');
        
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'danger');
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
            $this->flashMessage('Vinohrad se nepodařilo upravit.', 'danger');
        }
    }

    public function handleRemoveImage(int $id): void
    {
        $vineyard = $this->vineyardRepository->getVineyardById($id);
        if ($vineyard === null) {
            $this->flashMessage('Vinohrad nebyl nalezen.', 'danger');
            $this->redirect('dashboard');
        }

        if ($vineyard->getImage() === null) {
            $this->flashMessage('Vinohrad nemá žádný obrázek.', 'warning');
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
            $this->flashMessage('Obrázek se nepodařilo odstranit.', 'danger');
        }

        $this->redirect('edit', $id);
    }

    public function renderShow(int $vineyardId): void
    {
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'danger');
            $this->redirect('dashboard');
        }

        // Get wine rows and varieties for this vineyard
        /** @var WineRow[] $wineRows */
        $wineRows = $this->vineyardRepository->getWineRowsOnVineyard($vineyardId);
        $wineVarieties = $this->vineyardRepository->getUniqueWineVarietiesOnVineyard($vineyardId);
        $vineyard->setWineRowsCount(count($wineRows));
        $vineyard->setWineVarietiesCount(count($wineVarieties));

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
        $this->template->imageRepository = $this->imageRepository;
    }


    public function createComponentAddWineRowForm(): Form
    {
        // todo
        $form = new Form;

        $form->addInteger("rowNumber", "Číslo řádku")
            ->setRequired('Zadejte číslo řádku.');

        $form->addInteger("year", "Rok")
            ->setRequired('Zadejte rok.');

        $form->addInteger('headCount', 'Počet hlav')
            ->setRequired('Zadejte počet hlav.');

        $form->addSelect('variety', 'Odrůda vína')
            ->setRequired('Vyberte odrůdu vína.');
        // todo items

        $form->addSubmit('save', 'Přidat řádek');

        $form->onSuccess[] = [$this, 'addWineRowFormSucceeded'];

        return $form;
    }

    public function addWineRowFormSucceeded(Form $form, array $values): void
    {

    }


    public function renderEditWineRow(int $vineyardId): void
    {
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'danger');
            $this->redirect('dashboard');
        }

        // Get wine rows for this vineyard
        $wineRows = $this->vineyardRepository->getWineRowsOnVineyard($vineyardId);

        $this->template->vineyard = $vineyard;
        $this->template->wineRows = $wineRows;
    }

    public function renderAddWineRow(int $vineyardId): void
    {
        $vineyard = $this->vineyardRepository->getVineyardById($vineyardId);
        if ($vineyard === null) {
            Debugger::log("Vineyard with ID $vineyardId not found", ILogger::ERROR);
            $this->flashMessage('Požadovaný vinohrad nebyl nalezen.', 'danger');
            $this->redirect('dashboard');
        }

        $this->template->vineyard = $vineyard;
    }

    public function handleDeleteWineRow(int $id): void
    {
        
        $this->redirect('this');
    }




}
