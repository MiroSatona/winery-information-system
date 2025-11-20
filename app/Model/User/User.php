<?php

//FILE:     User.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Class for internal representation of the user in the system.

namespace App\Model\User;
use DateTimeInterface;

/**
 * Class User
 * Class for internal represetation of User for database.
 */
class User{
    /** @var int $id ID of user in the database. */
    private int $id;
    /** @var string $name Name of the user. */
    private string $name;
    /** @var bool $state State of the user. -> active | inactive  <=> true | false*/
    private bool $state;
    /** @var string $surname Surname of the user. */
    private string $surname;
    /** @var string $username Username of the user in the system. */
    private string $username;
    /** @var string $email Email of the user. */
    private string $email;
    /** @var string|null  $phoneNumber Phone number of the user. */
    private ?string $phoneNumber;
    /** @var string|null  $city City where user lives. */
    private ?string $city;
    /** @var string|null  $postcode Postal code of the user's address. */
    private ?string $postcode;
    /** @var string|null  $street Street of the user's address. */
    private ?string $street;
    /** @var string|null  $houseNumber House number of the user's address. */
    private ?string $houseNumber;

    /** @var DateTimeInterface $registrationTimestamp Time of user registration. */
    private DateTimeInterface $registrationTimestamp;

    /** @var bool $isUser Flag of user role. */
    private bool $isUser;
    /** @var bool $isWorker Flag of worker role. */
    private bool $isWorker;
    /** @var bool $isVinter Flag of vinter role. */
    private bool $isVinter;
    /** @var bool $isAdmin Flag of admin role. */
    private bool $isAdmin;
    
    /**
     * Constructor of the User class.
     *
     * @param integer $id ID of the user in the database.
     * @param string $name Name of the user.
     * @param boolean $state State of the user (active/inactive).
     * @param string $surname Surname of the user.
     * @param string $username Username of the user.
     * @param string $email Email of the user.
     * @param string|null $phoneNumber Phone number of the user.
     * @param string|null $city City where the user lives.
     * @param string|null $postcode Postal code of the user's address.
     * @param string|null $street Street of the user's address.
     * @param string|null $houseNumber House number of the user's address.
     * @param DateTimeInterface $registrationTimestamp Time of user registration.
     * @param boolean $isUser Flag of user role.
     * @param boolean $isWorker Flag of worker role.
     * @param boolean $isVinter Flag of vinter role.
     * @param boolean $isAdmin Flag of admin role.
     */
    public function __construct(
        int $id,
        string $name,
        bool $state,
        string $surname,
        string $username,
        string $email,
        ?string $phoneNumber,
        ?string $city,
        ?string $postcode,
        ?string $street,
        ?string $houseNumber,
        DateTimeInterface $registrationTimestamp,
        bool $isUser,
        bool $isWorker,
        bool $isVinter,
        bool $isAdmin
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->state = $state;
        $this->surname = $surname;
        $this->username = $username;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->city = $city;
        $this->postcode = $postcode;
        $this->street = $street;
        $this->houseNumber = $houseNumber;
        $this->registrationTimestamp = $registrationTimestamp;
        $this->isUser = $isUser;
        $this->isWorker = $isWorker;
        $this->isVinter = $isVinter;
        $this->isAdmin = $isAdmin;
    }

    /**
     * getId
     * Getter for ID of the user.
     * @return int ID of the user.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * getSurname
     * Getter for surname of the user.
     * @return string Surname of the user.
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * getUserName
     * Getter for username of the user.
     * @return string Username of the user.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * getName
     * Getter for name of the user.
     * @return string Name of the user.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getState(): bool {
        return $this->state;
    }

    /**
     * getEmail
     * Getter for email of the user.
     * @return string Email of the user.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * getPhoneNumber
     * Getter for phone number of the user.
     * @return string|null Phone number of the user.
    */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * getCity
     * Getter for city where user lives.
     * @return string|null City where user lives.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * getPostCode
     * Getter for poset code of user.
     * @return string|null Post code of place where user lives.
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * getStreet
     * Getter for street of user.
     * @return string|null Street where user lives.
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * getHouseNumber
     * Getter for house number where user lives.
     * @return string|null House number where user lives.
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * getTimeStamp
     * Getter for registration timestamp of the user.
     * @return DateTimeInterface Registration timestamp of the user.
     */
    public function getTimeStamp(): DateTimeInterface
    {
        return $this->registrationTimestamp;
    }

    /**
     * isUser
     * Getter of flag if the user has role of base user.
     * @return bool True if he has role of user, otherwise false.
     */
    public function isUser(): bool
    {
        return $this->isUser;
    }

    /**
     * isWorker
     * Getter of flag if the user has role of worker.
     * @return bool True if he has role of worker, otherwise false.
     */
    public function isWorker(): bool
    {
        return $this->isWorker;
    }

    /**
     * isVinter
     * Getter of flag if the user has role of vinter.
     * @return bool True if he has role of vinter, otherwise false.
     */
    public function isVinter(): bool
    {
        return $this->isVinter;
    }

    /**
     * isAdmin
     * Getter of flag if the user has role of admin.
     * @return bool True if he has role of admin, otherwise false.
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * getFullName
     * Getter for full name of the user -> concatenation of name and surname.
     * @return string Full name of the user.
     */
    public function getFullName(): string
    {
        return $this->name . ' ' . $this->surname;
    }

    /**
     * getRoles
     * Getter for array of user all roles.
     * @return string[] Array of user roles.
     */
    public function getRoles(): array
    {
        /** @var string[] $roles Array of user roles */
        $roles = [];

        if ($this->isUser) {
            $roles[] = 'Uživatel';
        }
        if ($this->isWorker) {
            $roles[] = 'Pracovník';
        }
        if ($this->isVinter) {
            $roles[] = 'Vinař';
        }
        if ($this->isAdmin) {
            $roles[] = 'Admin';
        }
        return $roles;
    }
}