<?php

namespace App\Model\Bottle;

final class Bottle {
    public function __construct(
        public int $taskId,
        public int $wineId,
        public int $bottlesCount,
        public float $bottleVolume,
    ) {}
}


