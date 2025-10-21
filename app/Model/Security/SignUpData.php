<?php

declare(strict_types=1);

namespace App\Model\Security;

class SignUpData
{
    public function __construct(
        private string $name,
        private string $surname,
        private string $username,
        private string $email,
        private ?string $phone,
        private ?string $city,
        private ?string $street,
        private ?string $houseNumber,
        private ?string $postalCode,
        private string $password
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
