<?php
//FILE:     ProfilePresenter.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Presenter for the User Profile

namespace App\Presentation\Profile;

use App\Presentation\BasePresenter;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette\Application\UI\Form;
use App\Model\Validation\ValidationDefinitions;
use App\Model\Profile\ProfileModel;

/**
 * Presenter for user profile management
 * Handles viewing and editing user profile information and password changes
 */
class ProfilePresenter extends BasePresenter
{
    /** @var int|null ID of the user who is logged in */
    private ?int $userId;

    /**  @var UserRepository Repository used to fetch user data.*/
    private UserRepository $userRepository;
    /** @var ValidationDefinitions Repository used for validation definitions. */
    private ValidationDefinitions $validationDefinitions;
    /** @var ProfileModel Model used for profile. */
    private ProfileModel $profileModel;

    /**
     * ProfilePresenter constructor
     * @param UserRepository $userRepository Repository used to fetch user data
     * @param ValidationDefinitions $validationDefinitions Repository used for validation definitions
     * @param ProfileModel $profileModel Model used for profile operations
     */
    public function __construct(UserRepository $userRepository, ValidationDefinitions $validationDefinitions, ProfileModel $profileModel)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->validationDefinitions = $validationDefinitions;
        $this->profileModel = $profileModel;
    }

    /**
     * Startup method called before any action
     * Initializes the userId from the current logged-in user
     * @return void
     */
    public function startup(): void
    {
        parent::startup();
        $this->userId = $this->getUser()->isLoggedIn() ? $this->getUser()->getId() : null;
    }

    /**
     * Renders the default profile view
     * Displays the current user's profile information
     * @return void
     */
    public function renderDefault(): void
    {
        if ($this->userId === null) {
            $this->redirect('Sign:in');
        }

        /** @var User|null $currentUser Instance of the current user. */
        $currentUser = $this->userRepository->getUserById((int) $this->userId);

        if ($currentUser === null) {
            $this->redirect('Sign:in');
        }

        $this->template->currentUser = $currentUser;
    }
    
    /**
     * Renders the profile edit view
     * Loads user data and populates the edit form with current values
     * @return void
     */
    public function renderEdit():void
    {

        if ($this->userId === null) {
            $this->redirect('Sign:in');
        }

        /** @var User|null $user User which will be added. */

        $user = $this->userRepository->getUserById((int)$this->userId);
        if ($user === null) {
            $this->redirect('Sign:in');
        }
        
        // Set values of user before edit
        $this['editProfileByUserForm']->setDefaults([
            'name'        => $user->getName(),
            'surname'     => $user->getSurname(),
            'email'       => $user->getEmail(),
            'phone'       => $user->getPhoneNumber(),
            'city'        => $user->getCity(),
            'street'      => $user->getStreet(),
            'houseNumber' => $user->getHouseNumber(),
            'postalCode'  => $user->getPostCode(),
            'username'    => $user->getUsername(),
        ]);
    }

    /**
     * Creates the form component for editing user profile
     * Form contains fields for personal information, address, and username
     * @return Form Form with data for editing user profile
     */
    public function createComponentEditProfileByUserForm(): Form
    {
        /** @var Form $form Form for editing profile form user*/
        $form = new Form();

        // Information fields
        $form->addText('name', 'Jméno')
            ->setRequired()
            ->addRule($form::MinLength, 'Jméno musí mít alespoň %d znaky.', $this->validationDefinitions::NAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Jméno nesmí mít více než %d znaků.', $this->validationDefinitions::NAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Jméno uživatele');

        $form->addText('surname', 'Příjmení')
            ->setRequired()
            ->addRule($form::MinLength, 'Příjmení musí mít alespoň %d znaky.', $this->validationDefinitions::SURNAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Příjmení nesmí mít více než %d znaků.', $this->validationDefinitions::SURNAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Příjmení uživatele');

        $form->addText('email', 'E-mail')
            ->setRequired()
            ->addRule($form::Email, 'Zadejte platnou e-mailovou adresu.', $this->validationDefinitions::EMAIL_PATTERN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'E-mail uživatele');

        $form->addText('phone', 'Telefonní číslo')
            ->addRule($form::MinLength, 'Telefonní číslo musí mít alespoň %d znaky.', $this->validationDefinitions::PHONE_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Telefonní číslo nesmí mít více než %d znaků.', $this->validationDefinitions::PHONE_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Telefonní číslo uživatele');

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

        $form->addText('username', 'Uživatelské jméno')
            ->setRequired()
            ->addRule($form::MinLength, 'Uživatelské jméno musí mít alespoň %d znaky.', $this->validationDefinitions::USERNAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Uživatelské jméno nesmí mít více než %d znaků.', $this->validationDefinitions::USERNAME_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Uživatelské jméno');
       
        // Button for submitting the form
        $form->addSubmit('send', 'Změnit údaje')
            ->setHtmlAttribute('class', 'btn btn-primary');
    
        $form->onSuccess[] = [$this, 'editDetailsByUser'];
        $form->onError[] = [$this, 'formError'];

        return $form;
    }

    /**
     * Creates the form component for resetting user password
     * Form requires old password and new password confirmation
     * @return Form The reset password form
     */
    public function createComponentResetPasswordForm(): Form
    {
        /** @var Form $form Form for reset password from user*/
        $form = new Form();

        $form->addPassword('oldPassword', 'Staré heslo:')
            ->setRequired('Zadejte prosím své staré heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->setHtmlAttribute('class', 'form-control');

        $form->addPassword('newPassword', 'Nové heslo:')
            ->setRequired('Zadejte nové heslo.')
            ->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->setHtmlAttribute('class', 'form-control');

        $form->addPassword('newPasswordAgain', 'Nové heslo znovu:')
            ->setRequired('Zadejte heslo znovu pro kontrolu.')
            ->addRule($form::Equal, 'Hesla se neshodují.', $form['newPassword'])
            ->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('changePassword', 'Změnit heslo')
            ->setHtmlAttribute('class', 'btn btn-outline-danger w-100 fw-bold');

        $form->onSuccess[] = [$this, 'resetPassword'];
        $form->onError[] = [$this, 'formError'];
    
        return $form;
    }

    /**
     * Handles user profile details editing by the user themselves
     * Compares old and new data and saves only the changed fields
     * @param Form $form Form for editing user details
     * @param \stdClass $values Values submitted from the form
     * @return void
     */
    public function editDetailsByUser(Form $form, \stdClass $values): void
    {
        if ($this->userId === null) {
            $this->flashMessage('Uživatel není přihlášen.', 'danger');
            $this->redirect('Sign:in');
        }

        /** @var User|null $oldUser User before edit */
        $oldUser = $this->userRepository->getUserById($this->userId);

        if(!$oldUser) {
            $this->flashMessage('Chyba nalezení  uživatele v databázi.', 'error');
            $this->redirect('Sign:in');
        }

        
        $oldUserData = [ 
            'NAME'        => $oldUser->getName(),
            'SURNAME'     => $oldUser->getSurname(),
            'PHONE_NUMBER'=> $oldUser->getPhoneNumber(),
            'CITY'        => $oldUser->getCity(),
            'STREET'      => $oldUser->getStreet(),
            'HOUSE_NUMBER'=> $oldUser->getHouseNumber(),
            'POST_CODE'   => $oldUser->getPostcode(),
            'USERNAME'    => $oldUser->getUsername(),
        ];

        $newUserData = [ 
            'NAME'        => $values->name,
            'SURNAME'     => $values->surname,
            'EMAIL'       => $values->email,
            'PHONE_NUMBER'=> $values->phone,
            'CITY'        => $values->city,
            'STREET'      => $values->street,
            'HOUSE_NUMBER'=> $values->houseNumber,
            'POST_CODE'   => $values->postalCode,
            'USERNAME'    => $values->username,
        ];


        $realChanges = array_diff_assoc($newUserData, $oldUserData);
        $returnMessage = $this->profileModel->editUserBySelf($realChanges, $this->userId);

        if ($returnMessage === '') {
            $this->flashMessage('Změny byly úspěšně uloženy.', 'success');
            $this->redirect('default');
        }

        // If there was an error while saving changes, attach it to the form so it is
        // shown to the user next to the form instead of redirecting away.
        $form->addError($returnMessage);

    }

    /**
     * Handles password reset for the logged-in user
     * Verifies old password and sets the new password
     * @param Form $form The password reset form
     * @param \stdClass $values Values submitted from the form (oldPassword, newPassword)
     * @return void
     */
    public function resetPassword(Form $form, \stdClass $values): void
    {   

        if ($this->userId === null) {
            $this->flashMessage('Uživatel není přihlášen.', 'danger');
            $this->redirect('Sign:in');
        }
        /** @var string $resultMessage Message with error message, or empty string if everything is OK. */
        $resultMessage = $this->profileModel->resetPasswordByUser($values->oldPassword, $values->newPassword, $this->userId);
        if ($resultMessage === '') {
            $this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
            $this->redirect('default');
        }

        // Add the password reset error to the form so the template can render it.
        $form->addError($resultMessage);
    }

    /**
     * Handles form errors by displaying them as flash messages
     * @param Form $form Form where the error was detected
     * @return void
     */
    public function formError(Form $form): void
    {
        $errors = $form->getErrors();

        if (!empty($errors)) {
            $this->flashMessage($errors[0], 'error');
        } else {
            $this->flashMessage('Došlo k neznámé chybě při odesílání formuláře.', 'error');
        }
    }
}