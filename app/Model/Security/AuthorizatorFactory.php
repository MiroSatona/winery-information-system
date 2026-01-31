<?php

//FILE:     AuthorizatorFactory.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Factory for creating the ACL authorizator.

declare(strict_types=1);

namespace App\Model\Security;

use Nette\Security\Permission;
use App\Model\Security\Role;

/**
 * Factory, that creates and configures the ACL authorizator
 */
class AuthorizatorFactory
{
    /**
     * Create the ACL authorizator
     *
     * @return Permission object with the definitions
     */
    public static function create(): Permission
    {
        $acl = new Permission();

        // define roles
        $acl->addRole(Role::GUEST->name);
        $acl->addRole(Role::USER->name);
        $acl->addRole(Role::WORKER->name);
        $acl->addRole(Role::VINTNER->name);
        $acl->addRole(Role::ADMIN->name);

        // define resources
        $acl->addResource(Resource::GUEST_PAGE->name);
        $acl->addResource(Resource::USER_PAGE->name);
        $acl->addResource(Resource::WORKER_PAGE->name);
        $acl->addResource(Resource::VINTNER_PAGE->name);
        $acl->addResource(Resource::ADMIN_PAGE->name);

        // define permissions
        $acl->allow(Role::GUEST->name, Resource::GUEST_PAGE->name);
        $acl->allow(Role::USER->name, Resource::USER_PAGE->name);
        $acl->allow(Role::WORKER->name, Resource::WORKER_PAGE->name);
        $acl->allow(Role::VINTNER->name, Resource::VINTNER_PAGE->name);
        $acl->allow(Role::ADMIN->name, Resource::ADMIN_PAGE->name);

        return $acl;
    }
}
