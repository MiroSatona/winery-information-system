<?php

declare(strict_types=1);

namespace App\Presentation\IS\WineVarieties;

use App\Model\Image\Image;
use App\Model\Image\ImageHandler;
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
        private ImageHandler $imageHandler
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

    public function renderDashboard(): void
    {
        $this->template->wineVarieties = $this->wineVarietyRepository->fetchAll();
    }

    public function renderAdd(): void
    {

    }


    public function createComponentAddWineVariety(): Form
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
        
        $form->addText('description', 'Popis odrůdy:')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
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
            $imagePath = $this->imageHandler->uploadImage($values->image);
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
        $result = $this->wineVarietyRepository->addWine($wineVariety);

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

    }

}
