<?php

declare(strict_types=1);

namespace App\Presentation\IS\Wine;

use Nette;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\Wine\WineRepository;
use Nette\Application\UI\Form;

final class WinePresenter extends BasePresenter
{
    private string $patternWines = '';

    public function __construct(private WineRepository $wineRepository)
    {
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
            ->setHtmlAttribute('placeholder', 'Vyhledávejte dle názvu, ročníku, stavu, odrůd, typu, přívlastku ...');

        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->onSuccess[] = [$this, 'searchWineFormSucceeded'];

        return $form;
    }

    public function searchWineFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->patternWines = $values->search;
    }

    public function renderDashboard(): void
    {
        /** @var Wine[] $wines */
        $wines = $this->wineRepository->fetchAllWines();

        $this->template->wines = $wines;
    }

    public function renderShow(int $wineId): void
    {

    }
}
