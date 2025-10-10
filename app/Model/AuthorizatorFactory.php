<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

class AuthorizatorFactory
{
	public static function create(): Nette\Security\Permission
	{
		$acl = new Nette\Security\Permission;

        $acl->addRole(ROLE::GUEST->name);
        $acl->addRole(ROLE::USER->name);
        $acl->addRole(ROLE::WORKER->name);
        $acl->addRole(ROLE::VINTNER->name);
        $acl->addRole(ROLE::ADMIN->name);

        //  define resources
        $acl->addResource(RESOURCE::GUEST_PAGE->name);
        $acl->addResource(RESOURCE::USER_PAGE->name);
        $acl->addResource(RESOURCE::WORKER_PAGE->name);
        $acl->addResource(RESOURCE::VINTNER_PAGE->name);
        $acl->addResource(RESOURCE::ADMIN_PAGE->name);

        // define permissions
        $acl->allow(ROLE::GUEST->name, RESOURCE::GUEST_PAGE->name);
        $acl->allow(ROLE::USER->name, RESOURCE::USER_PAGE->name);
        $acl->allow(ROLE::WORKER->name, RESOURCE::WORKER_PAGE->name);
        $acl->allow(ROLE::VINTNER->name, RESOURCE::VINTNER_PAGE->name);
        $acl->allow(ROLE::ADMIN->name, RESOURCE::ADMIN_PAGE->name);

		return $acl;
	}
}
