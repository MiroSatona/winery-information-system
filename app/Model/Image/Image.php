<?php

namespace App\Model\Image;

final class Image 
{
    public function __construct(
        public ?int $id, 
        public string $src,
        public string $name, 
        public string $description,
    )
    {
    }
}