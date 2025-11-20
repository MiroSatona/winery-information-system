<?php

//FILE:     LatteExtension.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Extension for Latte templating engine.

declare(strict_types=1);

namespace App\Presentation\Accessory;

use Latte\Extension;

/**
 * class LatteExtension
 * Class for extending Latte templating engine.
 */
final class LatteExtension extends Extension
{	
	/**
	 * getFilters
	 * Getter for own filters for Latte templating engine.
	 * @return  array<string, callable> Own filters for Latte templating engine.
	 */
	public function getFilters(): array
	{
		return [];
	}

	/**
	 * getFunctions
	 * Getter for own functions for Latte templating engine.
	 * @return array<string, callable> Own functions for Latte templating engine.
	 */
	public function getFunctions(): array
	{
		return [];
	}
}
