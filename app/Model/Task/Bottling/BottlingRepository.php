<?php

declare(strict_types=1);

namespace App\Model\Task\Bottling;

use Nette\Database\Explorer;


class BottlingRepository
{
    private Explorer $database;

    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }
}

