<?php

declare(strict_types=1);


namespace App\Model\Task;

use Nette\Database\Explorer;
use App\Model\Task\Treatment\TreatmentRepository;
use App\Model\Task\Bottling\BottlingRepository;
use App\Model\Task\Harvest\HarvestRepository;

class TaskRepository{
    private Explorer $database;
    private TreatmentRepository $treatmentRepository;
    private BottlingRepository $bottlingRepository;
    private HarvestRepository $harvestRepository;

    public function __construct(
        Explorer $database,
        TreatmentRepository $treatmentRepository,
        BottlingRepository $bottlingRepository,
        HarvestRepository $harvestRepository
    ) {
        $this->database = $database;
        $this->treatmentRepository = $treatmentRepository;
        $this->bottlingRepository = $bottlingRepository;
        $this->harvestRepository = $harvestRepository;
    }
}