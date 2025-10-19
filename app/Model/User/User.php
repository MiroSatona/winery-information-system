<?php

namespace App\Model\User;
use DateTimeInterface;


class User{
    private int $id;
    private string $name;
    private string $surname;
    private string $username;
    private string $email;
    private string $phoneNumber;
    private ?string $city;
    private ?string $postcode;
    private ?string $street;
    private ?string $houseNumber;

    private DateTimeInterface $registrationTimestamp;

    private bool $isUser;
    private bool $isWorker;
    private bool $isVinter;
    private bool $isAdmin;
    
    public function __construct(
        int $id,
        string $name,
        string $surname,
        string $username,
        string $email,
        string $phoneNumber,
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function getTimeStamp(): DateTimeInterface
    {
        return $this->registrationTimestamp;
    }

    public function isUser(): bool
    {
        return $this->isUser;
    }

    public function isWorker(): bool
    {
        return $this->isWorker;
    }

    public function isVinter(): bool
    {
        return $this->isVinter;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getFullName(): string
    {
        return $this->name . ' ' . $this->surname;
    }

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