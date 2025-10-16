<?php

declare(strict_types=1);

namespace App\Model\Security;


enum Role : string {
    case ADMIN = "admin";
    case USER = "user";
    case WORKER = "worker";
    case VINTNER = "vintner";
    case GUEST = "guest";
}