<?php

declare(strict_types=1);

namespace App\Presentation\IS\Users;

use Nette;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\Security\Role;
use App\Model\User\UserRepository;
use App\Model\Validation\ValidationDefinitions;
use App\Model\FlashMsgType;

final class UsersPresenter extends BasePresenter
{   
    /** @var UserRepository $userRepository Repository of all users in the system */
    private UserRepository $userRepository;
    /** @var ValidationDefinitions $validationDefinitions Validation definitions for user input */
    private ValidationDefinitions $validationDefinitions;
    /** @var string $pattern Search pattern for filtering users on the dashboard */
    private string $pattern = '';
    /** @var string $role Role for filtering users on the dashboard */
    private string $role = '';

    public function __construct(UserRepository $userRepository, ValidationDefinitions $validationDefinitions)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->validationDefinitions = $validationDefinitions;
    }

    public function startup(): void
    {   
        parent::startup();
        if (!$this->user->isInRole(Role::ADMIN->name)) {
            $this->flashMessage('Nemáte oprávnění pro přístup k uživatelům.', FlashMsgType::ERROR->value);
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

        // Call the method filterFormSucceeded on form success
        $form->onSuccess[] = [$this, 'filterFormSucceeded'];

        return $form;
    }

    public function createComponentAddUserForm(): Form
    {   
        /** @var Form  $form Form for adding new user */
        $form = new Form;

        // Information fields about the new user
        $form->addText('name', 'Jméno')
            ->addRule($form::MinLength, 'Jméno musí mít alespoň %d znaky.', $this->validationDefinitions::NAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Jméno nesmí mít více než %d znaků.', $this->validationDefinitions::NAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Jméno uživatele');

        $form->addText('surname', 'Příjmení')
            ->addRule($form::MinLength, 'Příjmení musí mít alespoň %d znaky.', $this->validationDefinitions::SURNAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Příjmení nesmí mít více než %d znaků.', $this->validationDefinitions::SURNAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Příjmení uživatele');

        $form->addText('email', 'E-mail')
            ->addRule($form::Email, 'Zadejte platnou e-mailovou adresu.', $this->validationDefinitions::EMAIL_PATTERN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'E-mail uživatele');

        $form->addText('phone', 'Telefonní číslo')
            ->addRule($form::MinLength, 'Telefonní číslo musí mít alespoň %d znaky.', $this->validationDefinitions::PHONE_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Telefonní číslo nesmí mít více než %d znaků.', $this->validationDefinitions::PHONE_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Telefonní číslo uživatele');

        // Contact details fields -> not required
        $form->addText('city', 'Město')
            ->addRule($form::MinLength, 'Město musí mít alespoň %d znaky.', $this->validationDefinitions::CITY_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Město nesmí mít více než %d znaků.', $this->validationDefinitions::CITY_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Město');

        $form->addText('street', 'Ulice')
            ->addRule($form::MinLength, 'Ulice musí mít alespoň %d znaky.', $this->validationDefinitions::STREET_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Ulice nesmí mít více než %d znaků.', $this->validationDefinitions::STREET_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Ulice');

        $form->addText('houseNumber', 'Číslo popisné')
            ->addRule($form::MinLength, 'Číslo popisné musí mít alespoň %d znaky.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Číslo popisné nesmí mít více než %d znaků.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '123');
        
        $form->addText('postalCode', 'PSČ')
            ->addRule($form::MinLength, 'PSČ musí mít alespoň %d znaky.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MIN)
            ->addRule($form::MaxLength, 'PSČ nesmí mít více než %d znaků.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MAX)
            ->addRule($form::Pattern, 'PSČ musí odpovídat formátu 12345.', $this->validationDefinitions::POSTAL_CODE_PATTERN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '12345');

        // Details of user for using the system
        $form->addText('username', 'Uživatelské jméno')
            ->addRule($form::MinLength, 'Uživatelské jméno musí mít alespoň %d znaky.', $this->validationDefinitions::USERNAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Uživatelské jméno nesmí mít více než %d znaků.', $this->validationDefinitions::USERNAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Uživatelské jméno');

        // Checkbox of roles for selecting roles of the new user
        $form->addCheckbox('isUser', 'Uživatel')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isWorker', 'Pracovník')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isVintner', 'Vinař')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isAdmin', 'Admin')
            ->setHtmlAttribute('class', 'form-check-input');
        
        // Button for submitting the form
        $form->addSubmit('send', 'Přidat uživatele')
            ->setHtmlAttribute('class', 'btn btn-primary');
    
        // Call the method addFormSucceeded on form success
        $form->onSuccess[] = [$this, 'addFormSucceeded'];

        return $form;
    }
    /**
     * renderDashboard
     * Method for rendering the user dashboard.
     *
     * @return void
     */
    public function renderDashboard(): void
    {   
        $this->template->users = $this->userRepository->fetchByAdmin($this->role, $this->pattern);
    }

    /**
     * filterFormSucceeded
     *
     * @param Form $form Form where the filter details are submitted.
     * @param \stdClass $values Filter values submitted by the user.
     * @return void
     */
    public function filterFormSucceeded(Form $form, \stdClass $values): void
    {   
        $this->pattern = $values->pattern;
        $this->role = $values->role;
    }

    
    /**
     * addFormSucceeded
     * Method for handling successful addition of a user.
     *
     * @param Form $form Form where the user details are submitted.
     * @param \stdClass $values User details submitted by the form.
     * @return void
     */
    public function addFormSucceeded(Form $form, \stdClass $values): void
    {   
        if(!$values->name || !$values->surname || !$values->email || !$values->phone || !$values->username) {
            $form->addError('Nejsou vyplněny všechny povinné údaje.');
            $this->flashMessage('Je potřeba vyplnit všechny povinné údaje(označené *).', 'error');
            return;
        }

        if(!$values->isUser && !$values->isWorker && !$values->isVintner && !$values->isAdmin) {
            $form->addError('Je potřeba vybrat alespoň jednu roli.');
            $this->flashMessage('Je potřeba vybrat alespoň jednu roli.', 'error');
            return;
        }
        $this->userRepository->addUser(
            $values->name,
            $values->surname,
            $values->username,
            $values->email,
            $values->phone,
            $values->city ?: null,
            $values->postalCode ?: null,
            $values->street ?: null,
            $values->houseNumber ?: null,
            (bool)$values->isUser,
            (bool)$values->isWorker,
            (bool)$values->isVintner,
            (bool)$values->isAdmin
        );

        $this->flashMessage('Uživatel byl úspěšně přidán.', 'success');
        $this->redirect('Users:dashboard');

    }

    /**
     * handleDeleteUser
     * Method for handling delete user.
     *
     * @param integer $userId Id of the user to delete.
     * @return void
     */
    public function handleDeleteUser(int $userId): void{
        /** @var string $deleteResult Result of the delete operation */
        $error = $this->userRepository->deleteUser($userId);

        if(!$error) {
            $this->flashMessage('Uživatel byl úspěšně odstraněn.', 'success');
        } else {
            $this->flashMessage($error, 'error');
        }

    }

    /**
     * renderEdit
     * Method for rendering the user edit form.
     *
     * @param integer $id Id of the user to edit.
     * @return void
     */
    public function renderEdit(int $id): void
    {   
        /** @var User $user Edited user */
        $user = $this->userRepository->getUserById($id);
        if (!$user) {
            $this->flashMessage('Uživatel nebyl nalezen.', 'error');
            $this->redirect('Users:dashboard');
        } else {
            $this->template->userEntity = $user;
            // Prefill edit form with existing values (if form component exists)
            /** @var Form|null $form */
            $form = $this->getComponent('editUserForm');
            if ($form instanceof Form) {
                $form->setDefaults([
                    'name' => $user->getName(),
                    'surname' => $user->getSurname(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhoneNumber(),
                    'city' => $user->getCity(),
                    'postalCode' => $user->getPostcode(),
                    'street' => $user->getStreet(),
                    'houseNumber' => $user->getHouseNumber(),
                    'username' => $user->getUsername(),
                    'isUser' => $user->isUser(),
                    'isWorker' => $user->isWorker(),
                    'isVintner' => $user->isVinter(),
                    'isAdmin' => $user->isAdmin(),
                ]);
            }
        }
    }

    /**
     * createComponentEditUserForm
     * Method for handling the edit user form.
     *
     * @return Form Form with edited user data.
     */
    public function createComponentEditUserForm(): Form
    {
        $form = new Form();

        // Name of the user
        $form->addText('name', 'Jméno')
              ->setRequired()
              ->addRule($form::MinLength, 'Jméno musí mít alespoň %d znaky.', $this->validationDefinitions::NAME_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Jméno nesmí mít více než %d znaků.', $this->validationDefinitions::NAME_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');

        // Surname of the user
        $form->addText('surname', 'Příjmení')
              ->setRequired()
              ->addRule($form::MinLength, 'Příjmení musí mít alespoň %d znaky.', $this->validationDefinitions::SURNAME_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Příjmení nesmí mít více než %d znaků.', $this->validationDefinitions::SURNAME_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');

        // Email of the user
        $form->addText('email', 'E-mail')
              ->setRequired()
              ->addRule($form::Email, 'Zadejte platnou e-mailovou adresu.', $this->validationDefinitions::EMAIL_PATTERN)
              ->setHtmlAttribute('class', 'form-control');

        // Phone number of the user
        $form->addText('phone', 'Telefon')
              ->setRequired()
              ->addRule($form::MinLength, 'Telefonní číslo musí mít alespoň %d znaky.', $this->validationDefinitions::PHONE_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Telefonní číslo nesmí mít více než %d znaků.', $this->validationDefinitions::PHONE_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');

        // User living details
        $form->addText('city', 'Město')
              ->addRule($form::MinLength, 'Město musí mít alespoň %d znaky.', $this->validationDefinitions::CITY_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Město nesmí mít více než %d znaků.', $this->validationDefinitions::CITY_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');
        $form->addText('street', 'Ulice')
              ->addRule($form::MinLength, 'Ulice musí mít alespoň %d znaky.', $this->validationDefinitions::STREET_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Ulice nesmí mít více než %d znaků.', $this->validationDefinitions::STREET_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');
        $form->addText('houseNumber', 'Číslo popisné')
              ->addRule($form::MinLength, 'Číslo popisné musí mít alespoň %d znaky.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Číslo popisné nesmí mít více než %d znaků.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');
        $form->addText('postalCode', 'PSČ')
              ->addRule($form::MinLength, 'PSČ musí mít alespoň %d znaky.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MIN)
              ->addRule($form::MaxLength, 'PSČ nesmí mít více než %d znaků.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MAX)
              ->addRule($form::Pattern, 'PSČ musí odpovídat formátu 12345.', $this->validationDefinitions::POSTAL_CODE_PATTERN)
              ->setHtmlAttribute('class', 'form-control');

        // Login details and roles
        $form->addText('username', 'Uživatelské jméno')
              ->setRequired()
              ->addRule($form::MinLength, 'Uživatelské jméno musí mít alespoň %d znaky.', $this->validationDefinitions::USERNAME_LENGTH_MIN)
              ->addRule($form::MaxLength, 'Uživatelské jméno nesmí mít více než %d znaků.', $this->validationDefinitions::USERNAME_LENGTH_MAX)
              ->setHtmlAttribute('class', 'form-control');
        $form->addCheckbox('isUser', 'Uživatel')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isWorker', 'Pracovník')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isVintner', 'Vinař')
            ->setHtmlAttribute('class', 'form-check-input');
        $form->addCheckbox('isAdmin', 'Admin')
            ->setHtmlAttribute('class', 'form-check-input');

        // Submit button
        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn btn-primary');

        // Button for resetting password
        $form->addSubmit('resetPassword', 'Resetovat heslo')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-outline-danger')
            ->onClick[] = [$this, 'resetPassword'];

        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * editFormSucceeded
     * Method for handling edit of a user from edit form from edit.latte.
     * 
     * @param Form $form Form where the user details are submitted.
     * @param array $values User details submitted by the form.
     * @return void
     */
    public function editFormSucceeded(Form $form, array $values): void{

        /** @var int $id Id of user which will be edited */
        $id = (int) $this->getParameter('id');

        /** @var User|null $oldUser User before edit */
        $oldUser = $this->userRepository->getUserById($id);

        if(!$oldUser) {
            $this->flashMessage('Chyba nalezení  uživatele v databázi.', 'error');
            $this->redirect('Users:dashboard');
        }

        $oldUserData = [ 
            'NAME'        => $oldUser->getName(),
            'SURNAME'     => $oldUser->getSurname(),
            'EMAIL'       => $oldUser->getEmail(),
            'PHONE_NUMBER'=> $oldUser->getPhoneNumber(),
            'CITY'        => $oldUser->getCity(),
            'STREET'      => $oldUser->getStreet(),
            'HOUSE_NUMBER'=> $oldUser->getHouseNumber(),
            'POST_CODE'   => $oldUser->getPostcode(),
            'USERNAME'    => $oldUser->getUsername(),
            'ROLE_USER'   => $oldUser->isUser(),
            'ROLE_WORKER' => $oldUser->isWorker(),
            'ROLE_VINTNER'=> $oldUser->isVinter(),
            'ROLE_ADMIN'  => $oldUser->isAdmin(),
        ];

        $newUserData = [ 
            'NAME'        => $values['name'],
            'SURNAME'     => $values['surname'],
            'EMAIL'       => $values['email'],
            'PHONE_NUMBER'=> $values['phone'],
            'CITY'        => $values['city'],
            'STREET'      => $values['street'],
            'HOUSE_NUMBER'=> $values['houseNumber'],
            'POST_CODE'   => $values['postalCode'],
            'USERNAME'    => $values['username'],
            'ROLE_USER'   => (bool)$values['isUser'],
            'ROLE_WORKER' => (bool)$values['isWorker'],
            'ROLE_VINTNER'=> (bool)$values['isVintner'],
            'ROLE_ADMIN'  => (bool)$values['isAdmin'],
        ];


        $realChanges = array_diff_assoc($newUserData, $oldUserData);
        bdump($realChanges);

        if(empty($realChanges)) {
            $this->flashMessage('Nebyly provedeny žádné změny.', 'info');
            $this->redirect('Users:dashboard');
        }

        /** @var string $editResult Result of the edit operation */
        $editResult = $this->userRepository->editUserByAdmin($id, $realChanges);

        if(!$editResult) {
            $this->flashMessage('Údaje uživatele byly úspěšně upraveny.', 'success');
            $this->redirect('Users:dashboard');
        } else {
            $this->flashMessage('Údaje uživatele se nepodařilo upravit.', 'error');
        }
        

    
    }

    /**
     * resetPassword
     * Method for handle reset password from edit form from edit.latte.
     * 
     * @return void
     */
    public function resetPassword(): void{

        /** @var int $id Id of user for password reset */
        $id = (int) $this->getParameter('id');

        /** @var string $returnMsg Result message of the password reset.*/
        $returnMsg = $this->userRepository->resetPasswordByAdmin($id);

        if(!$returnMsg) {
            $this->flashMessage('Heslo bylo úspěšně resetováno. Nové heslo bylo odesláno.', 'success');
        } else {
            $this->flashMessage($returnMsg, 'error');
        }

        $this->redirect('Users:dashboard');
        
   }

}
