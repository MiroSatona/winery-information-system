<?php

declare(strict_types=1);

namespace App\Presentation\IS\Users;

use Nette;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\User\UserRepository;

final class UsersPresenter extends BasePresenter
{   
    /** @var UserRepository $userRepository Repository of all users in the system */
    private UserRepository $userRepository;
    /** @var string $pattern Search pattern for filtering users */
    private string $pattern = '';
    /** @var string $role Role for filtering users */
    private string $role = '';

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    public function startup(): void
    {   
        ;
        parent::startup();
        if (!$this->user->isInRole(Role::ADMIN->name)) {
            $this->redirect(':Home:default');
        }
    }

    public function createComponentFilterForm(): Form
    {   
        /** @var Form  $form Form for filtering users */
        $form = new Form;

        // Set method get for getting parameters for filtering
        $form->getElementPrototype()->method = 'get';
        // Set class for form layout
        $form->getElementPrototype()->class = 'd-flex flex-row gap-3 justify-content-center align-items-center flex-grow-1 mx-4';

        // Set input for searching users by typing
        $form->addText('pattern', '')
            ->setHtmlAttribute('type', 'search')
            ->setHtmlAttribute('placeholder', 'Hledat uživatele')
            ->setHtmlAttribute('class', 'form-control flex-grow-1')
            ->setHtmlAttribute('style', 'width: 400px;');

        // Set selection bar for filtering by role
        $form->addSelect('role', '', [
            '' => 'Všechny role',
            'user' => 'Uživatel',
            'worker' => 'Pracovník',
            'vintner' => 'Vinař',
            'admin' => 'Admin',
        ])
        ->setHtmlAttribute('class', 'form-select')
        ->setHtmlAttribute('style', 'width: 180px;');

        // Button for submitting the form
        $form->addSubmit('send', 'Hledat')
            ->setHtmlAttribute('class', 'btn btn-primary');
        
        // Config form rendering
        $formRender = $form->getRenderer();
        $formRender->wrappers['controls']['container'] = null;
        $formRender->wrappers['pair']['container'] = null;
        $formRender->wrappers['label']['container'] = null;
        $formRender->wrappers['control']['container'] = null;
        
        // Call the method parseResult on form success
        $form->onSuccess[] = [$this, 'parseResult'];

        return $form;
    }

    

    public function renderDashboard(): void
    {   
        $this->template->users = $this->userRepository->fetchByAdmin($this->role, $this->pattern);
    }

    public function parseResult(Form $form, \stdClass $values): void
    {   
        $this->pattern = $values->pattern;
        $this->role = $values->role;
    }
}
