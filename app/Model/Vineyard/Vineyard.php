<?php
//FILE:     Vineyard.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a vineyard.

declare(strict_types=1);

namespace App\Model\Vineyard;

use App\Model\Image\Image;

/**
 * Represents a single Vineyard entity in the system
 */
final class Vineyard
{
    // number of wine rows on this vineyard (set separately)
    private int $wineRowsCount = 0;
    // number of different wine varieties on this vineyard (set separately)
    private int $wineVarietiesCount = 0;
    // total head count (number of plants) on this vineyard (set separately)
    private int $totalHeadCount = 0;

    /**
     * Vineyard constructor
     *
     * @param int|null $id
     * @param bool $active
     * @param string $name
     * @param string $location
     * @param Image|null $image
     * @param string|null $description
     * @param int $yearEstablished
     */
    public function __construct(
        private ?int $id,
        private bool $active,
        private string $name,
        private string $location,
        private ?Image $image,
        private ?string $description,
        private int $yearEstablished
    ) {
    }

    /**
     * Get the ID of the vineyard
     *
     * @return int|null
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Check if the vineyard is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get the name of the vineyard
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the vineyard
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the location of the vineyard
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Set the location of the vineyard
     *
     * @param string $location
     * @return void
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * Get the image of the vineyard
     *
     * @return Image|null
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * Set the image of the vineyard
     *
     * @param Image|null $image
     * @return void
     */
    public function setImage(?Image $image): void
    {
        $this->image = $image;
    }

    /**
     * Get the description of the vineyard
     *
     * @return string|null
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Set the description of the vineyard
     *
     * @param string|null $description
     * @return void
     */
    public function setDescription(string|null $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the year the vineyard was established
     *
     * @return int
     */
    public function getYearEstablished(): int
    {
        return $this->yearEstablished;
    }

    /**
     * Get the number of wine rows on this vineyard
     *
     * @return int
     */
    public function getWineRowsCount(): int
    {
        return $this->wineRowsCount;
    }

    /**
     * Set the number of wine rows on this vineyard
     *
     * @param int $count
     * @return void
     */
    public function setWineRowsCount(int $count): void
    {
        $this->wineRowsCount = $count;
    }

    /**
     * Get the number of wine varieties on this vineyard
     *
     * @return int
     */
    public function getWineVarietiesCount(): int
    {
        return $this->wineVarietiesCount;
    }

    /**
     * Set the number of wine varieties on this vineyard
     *
     * @param int $count
     * @return void
     */
    public function setWineVarietiesCount(int $count): void
    {
        $this->wineVarietiesCount = $count;
    }

    /**
     * Get the total head count (number of plants) on this vineyard
     *
     * @return int
     */
    public function getTotalHeadCount(): int
    {
        return $this->totalHeadCount;
    }

    /**
     * Set the total head count (number of plants) on this vineyard
     *
     * @param int $count
     * @return void
     */
    public function setTotalHeadCount(int $count): void
    {
        $this->totalHeadCount = $count;
    }
}
