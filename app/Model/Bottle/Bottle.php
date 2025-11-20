<?php
//FILE:     Bottle.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a bottle for shop purposes.

namespace App\Model\Bottle;

/**
 * Represents a Bottle entity for shop purposes
 */
final class Bottle {
    
    /**
     * Bottle constructor
     *
     * @param int $taskId
     * @param int $wineId
     * @param int $bottlesCount
     * @param float $bottleVolume
     */
    public function __construct(
        public int $taskId,
        public int $wineId,
        public int $bottlesCount,
        public float $bottleVolume,
    ) {}
}
