<?php

declare(strict_types=1);

namespace App\Model\Security;


enum Resource : string {
    case ADMIN_PAGE = "admin_page";
    case USER_PAGE = "user_page";
    case WORKER_PAGE = "worker_page";
    case VINTNER_PAGE = "vintner_page";
    case GUEST_PAGE = "guest_page";
}
