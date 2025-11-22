<?php

//FILE:     Role.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enumeration of security roles.

declare(strict_types=1);

namespace App\Model\Security;

/**
 * Security roles
 */
enum Role : string
{
    case ADMIN = "admin";
    case USER = "user";
    case WORKER = "worker";
    case VINTNER = "vintner";
    case GUEST = "guest";
}
