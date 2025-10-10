<?php

declare(strict_types=1);

namespace App\Presentation\Sign;

use Nette;
use App\Model\ROLE;
use Nette\Application\UI\Form;

final class SignPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;
    private Nette\Security\Passwords $passwords;

    public function __construct(Nette\Database\Explorer $database, Nette\Security\Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function renderDefault(): void
    {
        $this->user->isInRole(ROLE::GUEST->name);
    }

    public function createComponentSignInForm(): Form
    {
        $form = new Form;

        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte prosím své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím své heslo.');

        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = [$this, 'processSignInForm'];

        return $form;
    }

    public function processSignInForm(Form $form, \stdClass $values): void
    {
        try {
            $this->getUser()->login($values->username, $values->password);
            $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
            $this->redirect('Home:default');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Přihlášení se nezdařilo. Zkontrolujte své uživatelské jméno a heslo.');
        }
    }

    public function createComponentSignUpForm(): Form
    {
        $form = new Form;

        $form->addText('name', 'Jméno:')
            ->setRequired('Zadejte prosím své jméno.');

        $form->addText('surname', 'Příjmení:')
            ->setRequired('Zadejte prosím své příjmení.');

        $form->addText('email', 'E-mail:')
            ->setRequired('Zadejte prosím svůj e-mail.')
            ->addRule($form::Email, 'Zadejte prosím platný e-mail.');
        
        $form->addText('phone', 'Telefon:')
            ->setRequired('Zadejte prosím své telefonní číslo.')
            ->addRule($form::Pattern, 'Zadejte prosím platné telefonní číslo.', '\+?[0-9]{9}');
        
        $form->addText('city', 'Město:')
            ->setRequired('Zadejte prosím své město.');

        $form->addText('street', 'Ulice:')
            ->setRequired('Zadejte prosím svou ulici.');
        
        $form->addText('houseNumber', 'Číslo popisné:')
            ->setRequired('Zadejte prosím své číslo popisné.');
        
        $form->addText('postalCode', 'PSČ:')
            ->setRequired('Zadejte prosím své PSČ.')
            ->addRule($form::Pattern, 'Zadejte prosím platné PSČ.', '[0-9]{5}');

        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte prosím své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8);

        $form->addPassword('passwordConfirm', 'Heslo znovu:')
            ->setRequired('Potvrďte prosím své heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8)
            ->addRule($form::Equal, 'Hesla se neshodují.', $form['password']);

        $form->addSubmit('send', 'Registrovat se');

        $form->onSuccess[] = [$this, 'processSignUpForm'];

        return $form;
    }

    public function processSignUpForm(Form $form, \stdClass $values): void
    {
        // Check if username or email already exists
        $existingUser = $this->database->table('USER')
            ->where('username', $values->username)
            ->where('email', $values->email)
            ->fetch();

        if ($existingUser) {
            $form->addError('Uživatelské jméno nebo e-mail již existuje.');
            return;
        }

        // Generate salt and hash the password
        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $values->password . $salt;
        $hashedPassword = $this->passwords->hash($saltedPassword);

        // Insert new user into the database
        $this->database->table('USER')->insert([
            'name' => $values->name,
            'surname' => $values->surname,
            'email' => $values->email,
            'phone_number' => $values->phone,
            'username' => $values->username,
            'city' => $values->city,
            'post_code' => $values->postalCode,
            'street' => $values->street,
            'house_number' => $values->houseNumber,
            'pswd_salt' => $salt,
            'pswd_hash' => $hashedPassword,
            'role_user' => true, // Default role
            'role_admin' => false,
            'role_worker' => false,
            'role_vintner' => false,
        ]);

        $this->flashMessage('Registrace proběhla úspěšně. Nyní se můžete přihlásit.', 'success');
        $this->redirect('Sign:in');
    }
}