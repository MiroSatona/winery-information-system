<?php

declare(strict_types=1);

namespace App\Model\Vineyard;

use App\Model\Image\Image;

final class Vineyard
{
    private array $wineRows = [];
    private int $wineVarietiesCount = 0;

    public function __construct(
        private int $id,
        private string $name,
        private string $location,
        private ?Image $image,
        private string $description,
        private int $yearEstablished
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

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
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

    public function getYearEstablished(): int
    {
        return $this->yearEstablished;
    }

    public function getWineRows(): array
    {
        return $this->wineRows;
    }

    public function setWineRows(array $wineRows): void
    {
        $this->wineRows = $wineRows;
    }

    public function getWineVarietiesCount(): int
    {
        return $this->wineVarietiesCount;
    }

    public function setWineVarietiesCount(int $count): void
    {
        $this->wineVarietiesCount = $count;
    }
}