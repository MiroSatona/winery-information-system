<?php

declare(strict_types=1);

namespace App\Model\Task\Treatment;

use Nette\Database\Explorer;


class TreatmentRepository
{
    private Explorer $database;

    public function __construct(Explorer $database)
    {
        $this->database = $database;
    }
    
}
