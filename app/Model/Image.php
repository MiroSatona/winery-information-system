<?php

namespace App\Model;

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