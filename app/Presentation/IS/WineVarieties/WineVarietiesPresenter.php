<?php

declare(strict_types=1);

namespace App\Presentation\IS\WineVarieties;

use App\Model\Image\Image;
use App\Model\Image\ImageRepository;
use Nette;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Validation\ValidationDefinitions;
use App\Model\Wine;
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\WineVariety\WineVarietyType;
use App\Model\WineVariety\WineVariety;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

final class WineVarietiesPresenter extends BasePresenter
{

    public function __construct(
        private WineVarietyRepository $wineVarietyRepository,
        private ImageRepository $imageRepository,
        private ValidationDefinitions $validationDefinitions
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
        $this->template->wineVarieties = $this->wineVarietyRepository->fetchAll();
    }

    public function renderAdd(): void
    {

    }

    public function renderEdit(int $id): void
    {
        $wineVariety = $this->wineVarietyRepository->fetchById($id);
        if (!$wineVariety) {
            $this->flashMessage('Odrůda vína nebyla nalezena.', 'error');
            $this->redirect('WineVarieties:dashboard');
        }
        
        // Populate form with current values
        $form = $this->getComponent('editWineVariety');
        $form['name']->setValue($wineVariety->getName());
        $form['color']->setValue($wineVariety->getColor()->value);
        $form['description']->setValue($wineVariety->getDescription());
        
        $this->template->wineVariety = $wineVariety;
        if ($wineVariety->getImage() !== null) {
            $this->template->imagePath = $this->imageRepository->getPathForImage($wineVariety->getImage());
        }
    }

    public function createComponentAddWineVariety(): Form
    {
        $form = new Form;

        $form->addText('name', 'Název odrůdy')
            ->addRule($form::MinLength, 'Název odrůdy musí mít alespoň %d znaků.', $this->validationDefinitions::WINE_VARIETY_NAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Název odrůdy nesmí přesáhnout %d znaků.', $this->validationDefinitions::WINE_VARIETY_NAME_LENGTH_MAX)
            ->setRequired('Zadejte prosím název odrůdy vína.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název odrůdy vína');
        
        $form->addSelect('color', 'Barva bobulí', [
            WineVarietyType::WHITE->value => 'Bílé',
            WineVarietyType::RED->value => 'Červené',
            WineVarietyType::PINK->value => 'Růžové',
            WineVarietyType::GREY->value => 'Šedé',
            WineVarietyType::BLUE->value => 'Modré',
        ])
            ->setRequired('Zadejte prosím barvu bobulí.')
            ->setHtmlAttribute('class', 'form-select')
            ->setHtmlAttribute('placeholder', 'Zadejte barvu bobulí.');
        
        $form->addTextArea('description', 'Popis odrůdy')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis odrůdy vína');
        
        $form->addUpload('image', 'Obrázek odrůdy vína')
            ->addRule($form::Image, 'Nahrajte prosím platný obrázek.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek odrůdy vína');
        
        $form->addSubmit('send', 'Přidat odrůdu vína')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold');

        $form->onSuccess[] = [$this, 'processAddWineVarietyForm'];

        return $form;
    }

    public function processAddWineVarietyForm(Form $form, \stdClass $values): void
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
            $imageEntity = new Image(null, $imagePath, $values->name, 'Obrázek odrůdy vína ' . $values->name);
        }

        $wineVariety = new WineVariety(
            null,
            $values->name,
            WineVarietyType::from($values->color),
            $imageEntity,
            $values->description
        );

        /** @var bool $result */
        $result = $this->wineVarietyRepository->addWineVariety($wineVariety);

        if ($result) {
            $this->flashMessage('Odrůda vína byla úspěšně přidána.', 'success');
            $this->redirect('WineVarieties:dashboard');
        } else {
            $form->addError('Přidání odrůdy vína se nezdařilo.');
            $this->flashMessage('Přidání odrůdy vína se nezdařilo.', 'error');
        }
    }

    public function handleDelete(int $id): void
    {
        /** @var bool $result */
        $result = $this->wineVarietyRepository->deleteWineVariety($id);
        if ($result) {
            $this->flashMessage('Odrůda vína byla úspěšně odstraněna.', 'success');
        } else {
            $this->flashMessage('Odstranění odrůdy vína se nezdařilo.', 'error');
        }
        $this->redirect('WineVarieties:dashboard');
    }

    public function createComponentEditWineVariety(): Form
    {
        $form = new Form;

        $form->addText('name', 'Název odrůdy')
            ->addRule($form::MinLength, 'Název odrůdy musí mít alespoň %d znaků.', $this->validationDefinitions::WINE_VARIETY_NAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Název odrůdy nesmí přesáhnout %d znaků.', $this->validationDefinitions::WINE_VARIETY_NAME_LENGTH_MAX)
            ->setRequired('Zadejte prosím název odrůdy vína.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte název odrůdy vína');
        
        $form->addSelect('color', 'Barva bobulí', [
            WineVarietyType::WHITE->value => 'Bílé',
            WineVarietyType::RED->value => 'Červené',
            WineVarietyType::PINK->value => 'Růžové',
            WineVarietyType::GREY->value => 'Šedé',
            WineVarietyType::BLUE->value => 'Modré',
        ])
            ->setRequired('Zadejte prosím barvu bobulí.')
            ->setHtmlAttribute('class', 'form-select')
            ->setHtmlAttribute('placeholder', 'Zadejte barvu bobulí.');
        
        $form->addTextArea('description', 'Popis odrůdy')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis odrůdy vína');
        
        $form->addUpload('image', 'Obrázek odrůdy vína')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('accept', 'image/*');
        
        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn btn-primary w-100 fw-bold text-white');

        // Pre-fill form with current data in renderEdit method
        // Default values will be set in renderEdit method

        $form->onSuccess[] = [$this, 'processEditWineVarietyForm'];

        return $form;
    }

    public function processEditWineVarietyForm(Form $form, \stdClass $values): void
    {
        $id = (int)$this->getParameter('id');
        if (!$id) {
            Debugger::log('Invalid ID for editing wine variety: ' . $id, ILogger::ERROR);
            $this->flashMessage('Otevření detailu odrůdy vína se nezdařilo.', 'error');
            $this->redirect('WineVarieties:dashboard');
        }

        $wineVariety = $this->wineVarietyRepository->fetchById($id);
        if (!$wineVariety) {
            Debugger::log('Wine variety not found: ' . $id, ILogger::ERROR);
            $this->flashMessage('Odrůda vína nebyla nalezena.', 'error');
            $this->redirect('WineVarieties:dashboard');
        }

        $imageEntity = $wineVariety->getImage();
        
        /** @var FileUpload $newImage */
        $newImage = $values->image;
        if ($newImage->hasFile()) {
            // Upload new image
            $imagePath = $this->imageRepository->uploadImage($newImage);
            if ($imagePath === '') {
                Debugger::log('Image upload failed', ILogger::ERROR);
                $form->addError('Obrázek se nepodařilo nahrát.');
                $this->flashMessage('Obrázek se nepodařilo nahrát.', 'error');
                return;
            }
            $imageEntity = new Image(null, $imagePath, $values->name, 'Obrázek odrůdy vína ' . $values->name);
        }

        $updatedWineVariety = new WineVariety(
            $id,
            $values->name,
            WineVarietyType::from($values->color),
            $imageEntity,
            $values->description
        );

        /** @var bool $result */
        $result = $this->wineVarietyRepository->updateWineVariety($updatedWineVariety);

        if ($result) {
            $this->flashMessage('Odrůda vína byla úspěšně upravena.', 'success');
            $this->redirect('WineVarieties:dashboard');
        } else {
            $form->addError('Úprava odrůdy vína se nezdařila.');
            $this->flashMessage('Úprava odrůdy vína se nezdařila.', 'error');
        }
    }

    public function handleRemoveImage(int $id): void
    {
        $wineVariety = $this->wineVarietyRepository->fetchById($id);
        if (!$wineVariety) {
            Debugger::log('Wine variety not found: ' . $id, ILogger::ERROR);
            $this->flashMessage('Odrůda vína nebyla nalezena.', 'error');
            $this->redirect('WineVarieties:dashboard');
        }

        $updatedWineVariety = new WineVariety(
            $id,
            $wineVariety->getName(),
            $wineVariety->getColor(),
            null, // Remove image
            $wineVariety->getDescription()
        );

        /** @var bool $result */
        $result = $this->wineVarietyRepository->updateWineVariety($updatedWineVariety);

        if ($result) {
            $this->flashMessage('Obrázek byl úspěšně odstraněn.', 'success');
        } else {
            $this->flashMessage('Odstranění obrázku se nezdařilo.', 'error');
        }
        
        $this->redirect('WineVarieties:edit', ['id' => $id]);
    }

}
