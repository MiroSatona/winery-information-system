<?php
//FILE:     FlashMessageType.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Enum representing types of flash messages

declare(strict_types=1);

namespace App\Model;

/**
 * Supported types of flash messages (notifications)
 */
enum FlashMsgType: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case INFO = 'info';
    case WARNING = 'warning';
}
