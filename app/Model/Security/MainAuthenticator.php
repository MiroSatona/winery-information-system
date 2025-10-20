<?php

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

class MainAuthenticator implements Authenticator, IdentityHandler
{
	public function __construct(
		private Explorer $database,
		private Passwords $passwords,
	) {
	}

	public function authenticate(string $username, string $password): SimpleIdentity
	{
		$row = $this->database->table('USER')
			->where('username', $username)
			->fetch();

		if (!$row) {
			throw new AuthenticationException('User not found.');
		}

        $saltedPassword = $password . $row->PSWD_SALT;

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
     * @param IIdentity $identity
     * @return IIdentity
     */
    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        /* Do not modify for sleep */
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
