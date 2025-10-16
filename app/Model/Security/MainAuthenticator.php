<?php

declare(strict_types=1);

namespace App\Model\Security;

use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use App\Model\Security\Role;


class MainAuthenticator implements Authenticator
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
}
