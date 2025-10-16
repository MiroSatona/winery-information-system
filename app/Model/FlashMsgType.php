<?php


declare(strict_types=1);

namespace App\Model;

enum FlashMsgType: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case INFO = 'info';
    case WARNING = 'warning';
}