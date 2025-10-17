<?php

declare(strict_types=1);

namespace App\Presentation\IS\WineVarieties;

use App\Model\Image\Image;
use App\Model\Image\ImageRepository;
use Nette;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Wine;
use App\Model\WineVariety\WineVarietyRepository;
use App\Model\WineVariety\WineVarietyType;
use App\Model\WineVariety\WineVariety;
use Nette\Http\FileUpload;

final class WineVarietiesPresenter extends BasePresenter
{

    public function __construct(
        private WineVarietyRepository $wineVarietyRepository,
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
            ->setRequired('Zadejte prosím název odrůdy vína.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte název odrůdy vína');
        
        $form->addSelect('color', 'Barva bobulí:', [
            WineVarietyType::WHITE->value => 'Bílé',
            WineVarietyType::RED->value => 'Červené',
            WineVarietyType::PINK->value => 'Růžové',
            WineVarietyType::GREY->value => 'Šedé',
            WineVarietyType::BLUE->value => 'Modré',
        ])
            ->setRequired('Zadejte prosím barvu bobulí.')
            ->setHtmlAttribute('class', 'form-select form-select-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte barvu bobulí.');
        
        $form->addTextArea('description', 'Popis odrůdy:')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis odrůdy vína');
        
        $form->addUpload('image', 'Obrázek odrůdy vína:')
            ->addRule($form::Image, 'Nahrajte prosím platný obrázek.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Nahrajte obrázek odrůdy vína');
        
        $form->addSubmit('send', 'Přidat odrůdu vína')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 fw-bold');

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
                $form->addError('Obrázek se nepodařilo nahrát.');
                return; 
                // todo correct?????????
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
            // todo maybe use flashmessage as well????????
        }
        // todo
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

        $form->addText('name', 'Název odrůdy:')
            ->setRequired('Zadejte prosím název odrůdy vína.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte název odrůdy vína');
        
        $form->addSelect('color', 'Barva bobulí:', [
            WineVarietyType::WHITE->value => 'Bílé',
            WineVarietyType::RED->value => 'Červené',
            WineVarietyType::PINK->value => 'Růžové',
            WineVarietyType::GREY->value => 'Šedé',
            WineVarietyType::BLUE->value => 'Modré',
        ])
            ->setRequired('Zadejte prosím barvu bobulí.')
            ->setHtmlAttribute('class', 'form-select form-select-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte barvu bobulí.');
        
        $form->addTextArea('description', 'Popis odrůdy:')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('rows', 4)
            ->setHtmlAttribute('placeholder', 'Zadejte popis odrůdy vína');
        
        $form->addUpload('image', 'Obrázek odrůdy vína:')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('accept', 'image/*');
        
        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 fw-bold text-white');

        // Pre-fill form with current data in renderEdit method
        // Default values will be set in renderEdit method

        $form->onSuccess[] = [$this, 'processEditWineVarietyForm'];

        return $form;
    }

    public function processEditWineVarietyForm(Form $form, \stdClass $values): void
    {
        $id = (int)$this->getParameter('id');
        bdump($id);
        if (!$id) {
            $this->flashMessage('Neplatný identifikátor odrůdy vína.', 'error');
            $this->redirect('WineVarieties:dashboard');
        }

        $wineVariety = $this->wineVarietyRepository->fetchById($id);
        if (!$wineVariety) {
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
                $form->addError('Obrázek se nepodařilo nahrát.');
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
        }
    }

    public function handleRemoveImage(int $id): void
    {
        $wineVariety = $this->wineVarietyRepository->fetchById($id);
        if (!$wineVariety) {
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
