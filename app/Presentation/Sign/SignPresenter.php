<?php

declare(strict_types=1);

namespace App\Presentation\Sign;

use Nette;
use App\Model\FlashMsgType;
use App\Presentation\BasePresenter;
use App\Model\Security\SignUpData;
use App\Model\Security\SignUpWorker;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

final class SignPresenter extends BasePresenter
{
    public function __construct(private SignUpWorker $signUpWorker)
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
            ->setRequired('Zadejte prosím své uživatelské jméno.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte uživatelské jméno');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím své heslo.')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte heslo');

        $form->addSubmit('send', 'Přihlásit se')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 fw-bold');

        // Add Bootstrap classes to the form
        $form->onRender[] = function ($form) {
            foreach ($form->getControls() as $control) {
                if ($control instanceof Nette\Forms\Controls\TextInput || 
                    $control instanceof Nette\Forms\Controls\TextArea) {
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
            $this->flashMessage('Přihlášení se nezdařilo. Zkontrolujte své uživatelské jméno a heslo.', FlashMsgType::ERROR->value);
        }
    }

    public function createComponentSignUpForm(): Form
    {
        $form = new Form;

        // Personal information section
        $form->addText('name', 'Jméno:')
            ->setRequired('Zadejte prosím své jméno.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte jméno');

        $form->addText('surname', 'Příjmení:')
            ->setRequired('Zadejte prosím své příjmení.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte příjmení');

        $form->addText('email', 'E-mail:')
            ->setRequired('Zadejte prosím svůj e-mail.')
            ->addRule($form::Email, 'Zadejte prosím platný e-mail.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'name@example.com')
            ->setHtmlAttribute('type', 'email');
        
        $form->addText('phone', 'Telefon:')
            ->setRequired('Zadejte prosím své telefonní číslo.')
            ->addRule($form::Pattern, 'Zadejte prosím platné telefonní číslo.', '\+?[0-9]{9}')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '123456789');

        // Address section
        $form->addText('city', 'Město:')
            ->setRequired('Zadejte prosím své město.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte město');

        $form->addText('street', 'Ulice:')
            ->setRequired('Zadejte prosím svou ulici.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte ulici');
        
        $form->addText('houseNumber', 'Číslo popisné:')
            ->setRequired('Zadejte prosím své číslo popisné.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '123');
        
        $form->addText('postalCode', 'PSČ:')
            ->setRequired('Zadejte prosím své PSČ.')
            ->addRule($form::Pattern, 'Zadejte prosím platné PSČ.', '[0-9]{5}')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', '12345');

        // Account section
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte prosím své uživatelské jméno.')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zadejte uživatelské jméno');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Minimálně 8 znaků');

        $form->addPassword('passwordConfirm', 'Heslo znovu:')
            ->setRequired('Potvrďte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8)
            ->addRule($form::Equal, 'Hesla se neshodují.', $form['password'])
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Potvrďte heslo');

        $form->addSubmit('send', 'Registrovat se')
            ->setHtmlAttribute('class', 'btn btn-success btn-lg w-100 fw-bold');

        // Add Bootstrap classes to labels
        $form->onRender[] = function ($form) {
            foreach ($form->getControls() as $control) {
                if ($control instanceof Nette\Forms\Controls\TextInput || 
                    $control instanceof Nette\Forms\Controls\TextArea) {
                    $control->getLabelPart()->addAttributes(['class' => 'form-label fw-semibold']);
                }
            }
        };

        $form->onSuccess[] = [$this, 'processSignUpForm'];

        return $form;
    }

    public function processSignUpForm(Form $form, \stdClass $values): void
    {
        $data = new SignUpData(
            $values->name,
            $values->surname,
            $values->username,
            $values->email,
            $values->phone,
            $values->city,
            $values->street,
            $values->houseNumber,
            $values->postalCode,
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