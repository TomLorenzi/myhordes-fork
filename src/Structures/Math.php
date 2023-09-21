<?php

namespace App\Structures;

class Math {

	public static function Clamp(float $value, float $min = 0.0, float $max = 1.0): float {
		if ($value < $min)
			return $min;
		if ($value > $max)
			return $max;
		return $value;
	}
}