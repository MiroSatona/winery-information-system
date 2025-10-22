<?php

declare(strict_types=1);

namespace App\Presentation\Sign;

use Nette;
use App\Model\FlashMsgType;
use App\Presentation\BasePresenter;
use App\Model\Security\SignUpData;
use App\Model\Security\SignUpWorker;
use App\Model\Validation\ValidationDefinitions;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\TextArea;

final class SignPresenter extends BasePresenter
{
    public function __construct(private SignUpWorker $signUpWorker, private ValidationDefinitions $validationDefinitions)
    {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
    }

    public function renderDefault(): void
    {

    }

    public function renderIn(): void
    {

    }

    public function renderUp(): void
    {
        
    }

    public function createComponentSignInForm(): Form
    {
        $form = new Form;

        $form->addText('username', 'Uživatelské jméno:')
            ->addRule($form::Pattern, 'Zadejte prosím platné uživatelské jméno.', $this->validationDefinitions::USERNAME_PATTERN)
            ->setRequired('Zadejte prosím své uživatelské jméno.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte uživatelské jméno');

        $form->addPassword('password', 'Heslo:')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->setRequired('Zadejte prosím své heslo.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte heslo');

        $form->addSubmit('send', 'Přihlásit se')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 fw-bold');

        // Add Bootstrap classes to the form
        $form->onRender[] = function ($form) {
            foreach ($form->getControls() as $control) {
                if ($control instanceof TextInput || 
                    $control instanceof TextArea) {
                    $control->getLabelPart()->addAttributes(['class' => 'form-label fw-semibold']);
                }
            }
        };

        $form->onSuccess[] = [$this, 'processSignInForm'];

        return $form;
    }

    public function processSignInForm(Form $form, \stdClass $values): void
    {
        try {
            $this->getUser()->login($values->username, $values->password);
            $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
            $this->redirect('Home:default');
        } catch (AuthenticationException $e) {
            $this->flashMessage('Přihlášení se nezdařilo. Zkontrolujte své uživatelské jméno a heslo, případně Váš účet může být deaktivován.', FlashMsgType::ERROR->value);
        }
    }

    public function createComponentSignUpForm(): Form
    {
        $form = new Form;

        // Personal information section
        $form->addText('name', 'Jméno')
            ->addRule($form::MinLength, 'Jméno musí mít alespoň %d znaků.', $this->validationDefinitions::NAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Jméno může mít maximálně %d znaků.', $this->validationDefinitions::NAME_LENGTH_MAX)
            ->setRequired('Zadejte prosím své jméno.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte jméno');

        $form->addText('surname', 'Příjmení')
            ->addRule($form::MinLength, 'Příjmení musí mít alespoň %d znaků.', $this->validationDefinitions::SURNAME_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Příjmení může mít maximálně %d znaků.', $this->validationDefinitions::SURNAME_LENGTH_MAX)
            ->setRequired('Zadejte prosím své příjmení.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte příjmení');

        $form->addText('email', 'E-mail')
            ->setRequired('Zadejte prosím svůj e-mail.')
            ->addRule($form::Email, 'Zadejte prosím platný e-mail.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'name@example.com')
            ->setHtmlAttribute('type', 'email');
        
        $form->addText('phone', 'Telefon')
            ->addRule($form::MinLength, 'Telefonní číslo musí mít alespoň %d znaků.', $this->validationDefinitions::PHONE_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Telefonní číslo může mít maximálně %d znaků.', $this->validationDefinitions::PHONE_LENGTH_MAX)
            ->addRule($form::Pattern, 'Zadejte prosím platné telefonní číslo.', $this->validationDefinitions::PHONE_NUMBER_PATTERN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '123456789');

        // Address section
        $form->addText('city', 'Město')
            ->addRule($form::MinLength, 'Město musí mít alespoň %d znaků.', $this->validationDefinitions::CITY_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Město může mít maximálně %d znaků.', $this->validationDefinitions::CITY_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte město');

        $form->addText('street', 'Ulice')
            ->addRule($form::MinLength, 'Ulice musí mít alespoň %d znaků.', $this->validationDefinitions::STREET_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Ulice může mít maximálně %d znaků.', $this->validationDefinitions::STREET_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte ulici');
        
        $form->addText('houseNumber', 'Číslo popisné:')
            ->addRule($form::MinLength, 'Číslo popisné musí mít alespoň %d znaků.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MIN)
            ->addRule($form::MaxLength, 'Číslo popisné může mít maximálně %d znaků.', $this->validationDefinitions::HOUSE_NUMBER_LENGTH_MAX)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '123');
        
        $form->addText('postalCode', 'PSČ')
            ->addRule($form::MinLength, 'PSČ musí mít alespoň %d znaků.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MIN)
            ->addRule($form::MaxLength, 'PSČ může mít maximálně %d znaků.', $this->validationDefinitions::POSTAL_CODE_LENGTH_MAX)
            ->addRule($form::Pattern, 'Zadejte prosím platné PSČ.', $this->validationDefinitions::POSTAL_CODE_PATTERN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '12345');

        // Account section
        $form->addText('username', 'Uživatelské jméno')
            ->addRule($form::Pattern, 'Zadejte prosím platné uživatelské jméno: a-z, A-Z, 0-9, _, 4 - 30 znaků.', $this->validationDefinitions::USERNAME_PATTERN)
            ->setRequired('Zadejte prosím své uživatelské jméno.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte uživatelské jméno');

        $form->addPassword('password', 'Heslo')
            ->setRequired('Zadejte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Minimálně 8 znaků');

        $form->addPassword('passwordConfirm', 'Heslo znovu')
            ->setRequired('Potvrďte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', $this->validationDefinitions::PASSWORD_LENGTH_MIN)
            ->addRule($form::Equal, 'Hesla se neshodují.', $form['password'])
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Potvrďte heslo');

        $form->addSubmit('send', 'Registrovat se')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 fw-bold');

        $form->onError[] = function (Form $form) {
            $this->flashMessage('Formulář není správně vyplněn.', FlashMsgType::ERROR->value);
        };

        // Add Bootstrap classes to labels
        $form->onRender[] = function ($form) {
            foreach ($form->getControls() as $control) {
                if ($control instanceof TextInput || 
                    $control instanceof TextArea) {
                    $control->getLabelPart()->addAttributes(['class' => 'form-label fw-semibold']);
                }
            }
        };

        $form->onSuccess[] = [$this, 'processSignUpForm'];

        return $form;
    }

    public function processSignUpForm(Form $form, \stdClass $values): void
    {
        $phone = null;
        $city = null;
        $street = null;
        $houseNumber = null;
        $postalCode = null;

        if (!empty($values->phone)) {
            $phone = $values->phone;
        }

        if (!empty($values->city)) {
            $city = $values->city;
        }

        if (!empty($values->street)) {
            $street = $values->street;
        }

        if (!empty($values->houseNumber)) {
            $houseNumber = $values->houseNumber;
        }

        if (!empty($values->postalCode)) {
            $postalCode = $values->postalCode;
        }

        $data = new SignUpData(
            $values->name,
            $values->surname,
            $values->username,
            $values->email,
            $phone,
            $city,
            $street,
            $houseNumber,
            $postalCode,
            $values->password
        );

        $result = $this->signUpWorker->signUp($data);

        if (!$result) {
            $this->flashMessage('Uživatelské jméno nebo e-mail již existuje.', FlashMsgType::ERROR->value);
            return;
        }

        $this->flashMessage('Registrace proběhla úspěšně. Nyní se můžete přihlásit.', FlashMsgType::SUCCESS->value);
        $this->redirect('Sign:in');
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste odhlášeni.', FlashMsgType::SUCCESS->value);
        $this->redirect('Home:default');
    }
}