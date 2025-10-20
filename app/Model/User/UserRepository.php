<?php

namespace App\Model\User;

use Nette;
use Nette\Database\Explorer;
use App\Model\User\User;
use App\Model\Security\Role;
use Nette\Database\Row;
use Nette\Security\Passwords;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Utils\DateTime;

class UserRepository
{   
    /** @var Explorer Instance of the database explorer */
    private Explorer $database;
    private Passwords $passwords;
    private Mailer $mailer;

    /**
     * Constructor of UserRepository class
     *
     * @param Explorer $database Database of the system.
     * @param Passwords $passwords Nette Passwords class.
     * @param Mailer $mailer Nette Mailer class.
     * 
     */
    public function __construct(Explorer $database, Passwords $passwords, Mailer $mailer)
    {
        $this->database = $database;
        $this->passwords = $passwords;
        $this->mailer = $mailer;
    }

    /**
     * fetchAll
     * Method for fetch all users from database
     * @return User[] Array of all users in database.
     */
    public function fetchAll(): array
    {
        /** @var User[] $users Array of all users in database. */
        $users = [];
        $rows = $this->database->table('USER')->fetchAll();

        foreach ($rows as $row) {
            $users[] = new User(
                $row->ID,
                $row->NAME,
                $row->SURNAME,
                $row->USERNAME,
                $row->EMAIL,
                $row->PHONE_NUMBER,
                $row->CITY,
                $row->POST_CODE,
                $row->STREET,
                $row->HOUSE_NUMBER,
                $row->REGISTRATION_TIMESTAMP,
                (bool)$row->ROLE_USER,
                (bool)$row->ROLE_WORKER,
                (bool)$row->ROLE_VINTNER,
                (bool)$row->ROLE_ADMIN
            );
        }
        return $users;
    }


    /**
     * getUserById
     * Method for fetching a user by ID from the database.
     *
     * @param integer $id Id of the user to fetch from the database.
     * @return User|null Instance of User or null if not found.
     */
    public function getUserById(int $id): ?User
    {   
        $user = $this->database->table('USER')->get($id);
        if ($user === null) {
            return null;
        }

        return new User(
            $user->ID,
            $user->NAME,
            $user->SURNAME,
            $user->USERNAME,
            $user->EMAIL,
            $user->PHONE_NUMBER,
            $user->CITY,
            $user->POST_CODE,
            $user->STREET,
            $user->HOUSE_NUMBER,
            $user->REGISTRATION_TIMESTAMP,
            (bool)$user->ROLE_USER,
            (bool)$user->ROLE_WORKER,
            (bool)$user->ROLE_VINTNER,
            (bool)$user->ROLE_ADMIN
        );
    }

    /**
     * fetchByAdmin
     * Method for fetching users based on role and search pattern from form in the admin space in the system
     * 
     * @param string $role Filtering role.
     * @param string $pattern Search pattern for full name or login.
     * @return User[] Array of instances of user.
     */
    public function fetchByAdmin(string $role, string $pattern): array{
        
        /** @var string|null $roleColumn Role column name for where in SQL query */
        $roleColumn = null;

        // Get know if role was provided or not and set the role column
        switch($role){
            case Role::USER->value:
                $roleColumn = 'ROLE_USER';
                break;
            case Role::WORKER->value:
                $roleColumn = 'ROLE_WORKER';
                break;
            case Role::VINTNER->value:
                $roleColumn = 'ROLE_VINTNER';
                break;
            case Role::ADMIN->value:
                $roleColumn = 'ROLE_ADMIN';
                break;
            default:
                $roleColumn = null;
        }

        /** @var Row[] $queryResult Result of the database query */
        $queryResult = [];

        if(!$pattern && !$roleColumn){ // Any filtering is not provided -> fetch all users
            return $this->fetchAll();
        }
        else if(!$pattern && $roleColumn){ // Fetch independently by role
            $queryResult = $this->database->table('USER')->where($roleColumn, true)->fetchAll();
        }
        else if($pattern && !$roleColumn){ // Fetch independently by pattern from searchbar
            $regex = '%' . $pattern . '%';
            $queryResult = $this->database->table('USER')
                ->where('NAME LIKE ? OR SURNAME LIKE ? OR USERNAME LIKE ?', $regex, $regex, $regex)
                ->fetchAll();
        }
        else{ // Fetch independently on the pattern and role
            $regex = '%' . $pattern . '%';
            $queryResult = $this->database->table('USER')
                ->where($roleColumn, true)
                ->where('NAME LIKE ? OR SURNAME LIKE ? OR USERNAME LIKE ?', $regex, $regex, $regex)
                ->fetchAll();
        }

        /** @var User[] $users Array of user instances which is get by fetching by filters */
        $users = [];

        foreach ($queryResult as $userRow) {
            $users[] = new User(
                $userRow->ID,
                $userRow->NAME,
                $userRow->SURNAME,
                $userRow->USERNAME,
                $userRow->EMAIL,
                $userRow->PHONE_NUMBER,
                $userRow->CITY,
                $userRow->POST_CODE,
                $userRow->STREET,
                $userRow->HOUSE_NUMBER,
                $userRow->REGISTRATION_TIMESTAMP,
                (bool)$userRow->ROLE_USER,
                (bool)$userRow->ROLE_WORKER,
                (bool)$userRow->ROLE_VINTNER,
                (bool)$userRow->ROLE_ADMIN
            );
        }

        return $users;
    }

    /**
     * addUser
     * Method for add new user to the database.
     * @param string $name Name of the new user.
     * @param string $surname Surname of the new user.
     * @param string $username Username of the new user in the system.
     * @param string $email Email of the new user.
     * @param string|null $city City of the new user.
     * @param string|null $postcode Postcode of the new user.
     * @param string|null $street Street of the new user.
     * @param string|null $houseNumber House number of the new user.
     * @param bool $isUser Flag for add role of user.
     * @param bool $isWorker Flag for add role of worker.
     * @param bool $isVinter Flag for add role of vinter.
     * @param bool $isAdmin Flag for add role of admin.
     * @return void
     */
    public function addUser (
        string $name,
        string $surname,
        string $username,
        string $email,
        string $phoneNumber,
        ?string $city,
        ?string $postcode,
        ?string $street,
        ?string $houseNumber,
        bool $isUser,
        bool $isWorker,
        bool $isVinter,
        bool $isAdmin
    ) :void
    {   

    /** @var array $generatePasswordData array of new user password data */
    $generatePasswordData = $this->createNewPassword();

    /** @var string $password Password for the new user */
    $password = $generatePasswordData['password'];
    /** @var string $salt Salt of the password for the new user */
    $salt = $generatePasswordData['salt'];
    /** @var string $hashedPassword Hashed password for the new user */
    $hashedPassword = $generatePasswordData['hashedPassword'];


    // Begin transaction for inserting new user to the database
    $this->database->beginTransaction();
    try 
    {
        $this->database->table('USER')->insert([
            'NAME' => $name,
            'SURNAME' => $surname,
            'USERNAME' => $username,
            'EMAIL' => $email,
            'PHONE_NUMBER' => $phoneNumber,
            'CITY' => $city,
            'POST_CODE' => $postcode,
            'STREET' => $street,
            'HOUSE_NUMBER' => $houseNumber,
            'PHONE_NUMBER' => $phoneNumber,
            'PSWD_HASH' => $hashedPassword,
            'PSWD_SALT' => $salt,
            'REGISTRATION_TIMESTAMP' => new DateTime(),
            'ROLE_USER' => $isUser,
            'ROLE_WORKER' => $isWorker,
            'ROLE_VINTNER' => $isVinter,
            'ROLE_ADMIN' => $isAdmin
        ]);

        $this->database->commit();
    }
    catch (\Exception $exception) {
        $this->database->rollBack();
        throw $exception;
    }

    // Send email to the new user by method sendAddUserMail
    $this->sendAddUserMail($name, $email, $username, $password);
    }

    /**
     * deleteUser
     * Method for deleting user from the database.
     *
     * @param integer $userId Id of the user to delete.
     * @return string Error message or empty string on success.
     */
    public function deleteUser(int $userId): string
    {   
        /** @var string $error Error message */
        $error='';
        $this->database->beginTransaction();

        try 
        {
        
        /** Check if the use is not the last admin of the system */
        $adminUsers = $this->database->table('USER')->where('ROLE_ADMIN', true)->fetchAll();
        $deletedUser = $this->database->table('USER')->where('ID', $userId)->where('ROLE_ADMIN', true)->fetch();

        if (count($adminUsers) === 1 && $deletedUser) {
            $error = 'Nelze smazat posledního administrátora.';
            $this->database->rollBack();
            return $error;
        }

        /** Delete user  */

        $this->database->table('USER')->where('ID', $userId)->delete();
        $this->database->commit();
        return $error;

        }
        catch (\Exception $exception) {
            $this->database->rollBack();
            $error = 'Chyba při mazání uživatele: ' . $exception->getMessage();
            return $error;
        }
    }

    /**
     * sendAddUserMail
     * Method for send email to the new user with his login information.
     * 
     * @param string $name Name of the user.
     * @param string $email Email of the user.
     * @param string $username Login of the user.
     * @param string $password Password of the user.
     * @return void
     */
    private function sendAddUserMail(string $name, string $email, string $username, string $password): void
    {
        $htmlBody = <<<HTML
            <p>Dobrý den {$name},</p>
            <p>byl jste úspěšně přidán do systému <strong>Andrej Vinářství</strong>.</p>
            <p><strong>Přihlašovací údaje:</strong><br>
            Uživatelské jméno: <code>{$username}</code><br>
            Heslo: <code>{$password}</code></p>
            <p>S pozdravem,<br>
            <em>Andrejovo Vinářství</em></p>
            HTML;

        $mail = (new Message())
            ->setFrom('no-reply@andrejvinarstvi.cz', 'Andrej Vinářství')
            ->addTo($email, $name)
            ->setSubject('Byli jste přidáni do systému Andrej Vinářství')
            ->setHtmlBody($htmlBody);

        try {
            $mailer = new Nette\Mail\SendmailMailer;
            $mailer->send($mail);
        } catch (SendException $emailException) {
            throw $emailException;
        }

        return;
    }

    /**
     * createNewPassword
     * Method for creating a new random password, salt and hashed password.
     *
     * @return string[] Array containing 'password', 'salt' and 'hashedPassword'
     */
    private function createNewPassword() : array{
        // Generate random password
        $password = bin2hex(random_bytes(4));
        // Generate random salt for password hashing
        $salt = bin2hex(random_bytes(16));
        // Hash the password with the salt
        $saltedPassword = $password . $salt;
        $hashedPassword = $this->passwords->hash($saltedPassword);

        return ['password' => $password, 'salt' => $salt, 'hashedPassword' => $hashedPassword];
    }
}