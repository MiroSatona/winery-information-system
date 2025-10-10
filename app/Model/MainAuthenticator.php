<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\SimpleIdentity;

enum ROLE : string {
    case ADMIN = "admin";
    case USER = "user";
    case WORKER = "worker";
    case VINTNER = "vintner";
    case GUEST = "guest";
}

enum RESOURCE : string {
    case ADMIN_PAGE = "admin_page";
    case USER_PAGE = "user_page";
    case WORKER_PAGE = "worker_page";
    case VINTNER_PAGE = "vintner_page";
    case GUEST_PAGE = "guest_page";
}

class MainAuthenticator implements Nette\Security\Authenticator
{
	public function __construct(
		private Nette\Database\Explorer $database,
		private Nette\Security\Passwords $passwords,
	) {
	}

	public function authenticate(string $username, string $password): SimpleIdentity
	{
		$row = $this->database->table('USER')
			->where('username', $username)
			->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('User not found.');
		}

        bdump($row);
        $saltedPassword = $password . $row->PSWD_SALT;

		if (!$this->passwords->verify($saltedPassword, $row->PSWD_HASH)) {
			throw new Nette\Security\AuthenticationException('Invalid password.');
		}

        $roles = [];

        // user always has guest role
        $roles[] = ROLE::GUEST->name;

        // setup roles based on db data
        if ($row->ROLE_ADMIN) {
            $roles[] = ROLE::ADMIN->name;
        }
        if ($row->ROLE_USER) {
            $roles[] = ROLE::USER->name;
        }
        if ($row->ROLE_WORKER) {
            $roles[] = ROLE::WORKER->name;
        }
        if ($row->ROLE_VINTNER) {
            $roles[] = ROLE::VINTNER->name;
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
