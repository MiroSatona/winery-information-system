<?php

declare(strict_types=1);

namespace App\Model\Security;

use Nette\Database\Explorer;
use Nette\Security\Passwords;

class SignUpWorker
{
	public function __construct(
		private Explorer $database,
		private Passwords $passwords,
	) {
	}

    public function signUp(SignUpData $data): bool
    {
        // Check if username or email already exists
        $existingUser = $this->database->table('USER')
            ->where('username', $data->getUsername())
            ->where('email', $data->getEmail())
            ->fetch();

        if ($existingUser) {
            return false; // Username or email already taken
        }

        // Generate salt and hash the password
        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $data->getPassword() . $salt;
        $hashedPassword = $this->passwords->hash($saltedPassword);

        // Insert new user into the database
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
            'role_user' => true, // Default role
            'role_admin' => false,
            'role_worker' => false,
            'role_vintner' => false,
        ]);

        return true; // Registration successful
    }
	
}
