<?php

//FILE:     Image.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of an image.

namespace App\Model\Image;

/**
 * Represents a single Image entity in the system
 */
final class Image
{
    /**
     * Image constructor
     *
     * @param int|null $id
     * @param string $src
     * @param string $name
     * @param string $description
     */
    public function __construct(
        public ?int $id,
        public string $src,
        public string $name,
        public string $description,
    ) {
    }
}
