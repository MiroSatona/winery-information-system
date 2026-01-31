<?php

//FILE:     SignUpData.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Data class for user sign-up information.

declare(strict_types=1);

namespace App\Model\Security;

/**
 * Data class representing user sign-up information
 */
class SignUpData
{
    /**
     * SignUpData constructor
     *
     * @param string $name
     * @param string $surname
     * @param string $username
     * @param string $email
     * @param string|null $phone
     * @param string|null $city
     * @param string|null $street
     * @param string|null $houseNumber
     * @param string|null $postalCode
     * @param string $password
     */
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

    /**
     * Get the user first name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the user surname
     *
     * @return string
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * Get the user username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the user email address
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the user phone number
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Get the user city
     *
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get the user street
     *
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Get the user house number
     *
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * Get the user postal code
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Get the user password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
