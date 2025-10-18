<?php

namespace App\Model\User;

use Nette;
use Nette\Database\Explorer;
use App\Model\User\User;
use App\Model\Security\Role;


class UserRepository
{   
    /** @var Explorer Instance of the database explorer */
    private Explorer $database;

    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }

    public function fetchAll(): array
    {
        /** @var User[] $users */
        $users = [];
        $rows = $this->database->table('USER')->fetchAll();

        foreach ($rows as $row) {
            $users[] = new User(
                $row->ID,
                $row->NAME,
                $row->SURNAME,
                $row->USERNAME,
                $row->EMAIL,
                $row->CITY,
                $row->POST_CODE,
                $row->STREET,
                $row->HOUSE_NUMBER,
                $row->REGISTRATION_TIMESTAMP,
                (bool)$row->ROLE_USER,
                (bool)$row->ROLE_WORKER,
                (bool)$row->ROLE_VINTNER,
                (bool)$row->ROLE_ADMIN
            );
        }
        return $users;
    }

    public function getUserById(int $id): ?User
    {   
        $user = $this->database->table('USER')->get($id);
        if ($user === null) {
            return null;
        }

        return new User(
            $user->ID,
            $user->NAME,
            $user->SURNAME,
            $user->USERNAME,
            $user->EMAIL,
            $user->CITY,
            $user->POSTCODE,
            $user->STREET,
            $user->HOUSE_NUMBER,
            $user->REGISTRATION_TIMESTAMP,
            (bool)$user->IS_USER,
            (bool)$user->IS_WORKER,
            (bool)$user->IS_VINTER,
            (bool)$user->IS_ADMIN
        );
    }

    /**
     * Undocumented function
     *
     * @param string $role
     * @param string $pattern
     * @return User[]
     */
    public function fetchByAdmin(string $role, string $pattern): array{
        /** @var User[] $users */
        $users = [];

        /** @var string|null $roleColumn Role column name for where in SQL query */
        $roleColumn = null;

        switch($role){
            case Role::USER->value:
                $roleColumn = 'ROLE_USER';
                break;
            case Role::WORKER->value:
                $roleColumn = 'ROLE_WORKER';
                break;
            case Role::VINTNER->value:
                $roleColumn = 'ROLE_VINTNER';
                break;
            case Role::ADMIN->value:
                $roleColumn = 'ROLE_ADMIN';
                break;
            default:
                $roleColumn = null;
        }

        $queryResult = [];
        if(!$pattern && !$roleColumn){
            return $this->fetchAll();
        }
        else if(!$pattern && $roleColumn){
            $queryResult = $this->database->table('USER')->where($roleColumn, true)->fetchAll();
        }
        else if($pattern && !$roleColumn){
            $regex = '%' . $pattern . '%';
            $queryResult = $this->database->table('USER')
                ->where('NAME LIKE ? OR SURNAME LIKE ? OR USERNAME LIKE ?', $regex, $regex, $regex)
                ->fetchAll();
        }
        else{
            $regex = '%' . $pattern . '%';
            $queryResult = $this->database->table('USER')
                ->where($roleColumn, true)
                ->where('NAME LIKE ? OR SURNAME LIKE ? OR USERNAME LIKE ?', $regex, $regex, $regex)
                ->fetchAll();
        }

        $users = [];
        foreach ($queryResult as $userRow) {
            $users[] = new User(
                $userRow->ID,
                $userRow->NAME,
                $userRow->SURNAME,
                $userRow->USERNAME,
                $userRow->EMAIL,
                $userRow->CITY,
                $userRow->POST_CODE,
                $userRow->STREET,
                $userRow->HOUSE_NUMBER,
                $userRow->REGISTRATION_TIMESTAMP,
                (bool)$userRow->ROLE_USER,
                (bool)$userRow->ROLE_WORKER,
                (bool)$userRow->ROLE_VINTNER,
                (bool)$userRow->ROLE_ADMIN
            );
        }

        return $users;
    }
}