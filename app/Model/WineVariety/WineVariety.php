<?php

declare(strict_types=1);

namespace App\Model\WineVariety;

use App\Model\WineVariety\WineVarietyType;
use App\Model\Image;


final class WineVariety
{
    public function __construct(
        private ?int $id,
        private string $name,
        private WineVarietyType $color,
        private ?Image $image,
        private string $description
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getColor(): WineVarietyType
    {
        return $this->color;
    }

    public function setColor(WineVarietyType $color): void
    {
        $this->color = $color;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): void
    {
        $this->image = $image;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}