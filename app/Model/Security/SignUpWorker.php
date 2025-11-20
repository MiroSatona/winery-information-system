<?php
//FILE:     SignUpWorker.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:    	Worker class for handling user sign-up process.

declare(strict_types=1);

namespace App\Model\Security;

use Nette\Database\Explorer;
use Nette\Security\Passwords;

/**
 * Handles sign up process for new users
 */
class SignUpWorker
{
    /**
     * SignUpWorker constructor
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
     * Sign up a new user with the provided data
     *
     * @param SignUpData $data
     * @return bool true on success, false if username or email already exists
     */
    public function signUp(SignUpData $data): bool
    {
        // check if username or email already exists
        $existingUser = $this->database->table('USER')
            ->where('username', $data->getUsername())
            ->where('email', $data->getEmail())
            ->fetch();

        if ($existingUser) {
            return false; // username or email already taken
        }

        // generate salt and hash the password
        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $data->getPassword() . $salt;
        $hashedPassword = $this->passwords->hash($saltedPassword);

        // insert new user into the database
        $this->database->table('USER')->insert([
            'name' => $data->getName(),
            'surname' => $data->getSurname(),
            'email' => $data->getEmail(),
            'phone_number' => $data->getPhone(),
            'username' => $data->getUsername(),
            'city' => $data->getCity(),
            'post_code' => $data->getPostalCode(),
            'street' => $data->getStreet(),
            'house_number' => $data->getHouseNumber(),
            'pswd_salt' => $salt,
            'pswd_hash' => $hashedPassword,
            'role_user' => true, // default role
            'role_admin' => false,
            'role_worker' => false,
            'role_vintner' => false,
        ]);

        return true; // registration successful
    }
}
