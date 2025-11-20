<?php
//FILE:     WineVariety.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Internal representation of a wine variety.


declare(strict_types=1);

namespace App\Model\WineVariety;

use App\Model\WineVariety\WineVarietyType;
use App\Model\Image\Image;

/**
 * Represents single Wine Variety
 */
final class WineVariety
{
    /**
     * Wine Variety constructor
     *
     * @param integer|null $id
     * @param string $name
     * @param WineVarietyType $color
     * @param Image|null $image
     * @param string $description
     */
    public function __construct(
        private ?int $id,
        private string $name,
        private WineVarietyType $color,
        private ?Image $image,
        private string $description
    ) {
    }

    /**
     * Get ID of the wine variety
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get name of wine variety
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the wine variety
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the color (type) of the wine variety
     *
     * @return WineVarietyType
     */
    public function getColor(): WineVarietyType
    {
        return $this->color;
    }

    /**
     * Set the color (type) of the wine variety
     *
     * @param WineVarietyType $color
     * @return void
     */
    public function setColor(WineVarietyType $color): void
    {
        $this->color = $color;
    }

    /**
     * Get the image of the wine variety
     *
     * @return Image|null
     */
    public function getImage(): ?Image
    {
        return $this->image;
    }


    /**
     * Set the image of the wine variety
     *
     * @param Image|null $image
     * @return void
     */
    public function setImage(?Image $image): void
    {
        $this->image = $image;
    }


    /**
     * Get the description of the wine variety
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }


    /**
     * Set the description of the wine variety
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
