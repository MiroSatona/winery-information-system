<?php
//FILE:     MainAuthenticator.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Main authenticator for user login and identity handling.

declare(strict_types=1);

namespace App\Model\Security;

use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use App\Model\Security\Role;
use Nette\Security\IdentityHandler;

/**
 * Main authenticator class, that handles user authentication and identity management.
 */
class MainAuthenticator implements Authenticator, IdentityHandler
{
    /**
     * MainAuthenticator constructor
     *
     * @param Explorer $database
     * @param Passwords $passwords
     */
	public function __construct(
		private Explorer $database,
		private Passwords $passwords,
	) {
	}

    /**
     * Authenticate user with given username and password
     *
     * @param string $username
     * @param string $password
     * @return SimpleIdentity of the authenticated user
     * @throws AuthenticationException when authentication fails
     */
	public function authenticate(string $username, string $password): SimpleIdentity
	{
        // fetch user row from db
		$row = $this->database->table('USER')
			->where('username', $username)
			->fetch();

		if (!$row) {
			throw new AuthenticationException('User not found.');
		}

        if (!$row->ACTIVE) {
            throw new AuthenticationException('User account is inactive.');
        }

        $saltedPassword = $password . $row->PSWD_SALT;

        // verify password
		if (!$this->passwords->verify($saltedPassword, $row->PSWD_HASH)) {
			throw new AuthenticationException('Invalid password.');
		}

        $roles = [];

        // user always has guest role
        $roles[] = Role::GUEST->name;

        // setup roles based on db data
        if ($row->ROLE_ADMIN) {
            $roles[] = Role::ADMIN->name;
        }
        if ($row->ROLE_USER) {
            $roles[] = Role::USER->name;
        }
        if ($row->ROLE_WORKER) {
            $roles[] = Role::WORKER->name;
        }
        if ($row->ROLE_VINTNER) {
            $roles[] = Role::VINTNER->name;
        }

		return new SimpleIdentity(
			$row->ID,
			$roles,
			[
                'name' => $row->NAME,
                'surname' => $row->SURNAME,
                'email' => $row->EMAIL,
                'username' => $row->USERNAME
            ],
		);
	}

    /**
     * Sleep user identity
     * 
     * We do not need this function, but it is required by IdentityHandler interface.
     * 
     * @param IIdentity $identity
     * @return IIdentity
     */
    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        return $identity;
    }

    /**
     * Wakeup user identity
     * 
     * Each time user queries the Nette server -> recheck their roles and activity
     * 
     * @param IIdentity $identity
     * @return IIdentity
     */
    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {   
        $userRow = $this->database->table('USER')
            ->where('ID', $identity->getId())
            ->fetch();
        
        // if user not found, return null
        if (!$userRow)
            return null;

        if (!$userRow->ACTIVE) {
            return null;
        }

        $userData = [
            'name' => $userRow->NAME,
            'surname' => $userRow->SURNAME,
            'email' => $userRow->EMAIL,
            'username' => $userRow->USERNAME
        ];

        // user always has guest role
        $roles[] = Role::GUEST->name;

        // setup roles based on db data
        if ($userRow->ROLE_ADMIN) {
            $roles[] = Role::ADMIN->name;
        }
        if ($userRow->ROLE_USER) {
            $roles[] = Role::USER->name;
        }
        if ($userRow->ROLE_WORKER) {
            $roles[] = Role::WORKER->name;
        }
        if ($userRow->ROLE_VINTNER) {
            $roles[] = Role::VINTNER->name;
        }

        return new SimpleIdentity(
			$userRow->ID,
			$roles,
			$userData,
		);
    }
}
