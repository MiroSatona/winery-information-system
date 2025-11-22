<?php

//FILE:     Resource.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enumeration of security resources.

declare(strict_types=1);

namespace App\Model\Security;

/**
 * Security resources
 */
enum Resource : string
{
    case ADMIN_PAGE = "admin_page";
    case USER_PAGE = "user_page";
    case WORKER_PAGE = "worker_page";
    case VINTNER_PAGE = "vintner_page";
    case GUEST_PAGE = "guest_page";
}
