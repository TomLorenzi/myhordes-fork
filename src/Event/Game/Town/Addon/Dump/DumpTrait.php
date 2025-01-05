<?php

namespace App\Event\Game\Town\Addon\Dump;

trait DumpTrait
{
    public bool $dump_built = false;
	public bool $wood_dump_built = false;
	public bool $metal_dump_built = false;
	public bool $animal_dump_built = false;
	public bool $free_dump_built = false;
	public bool $weapon_dump_built = false;
	public bool $food_dump_built = false;
	public bool $defense_dump_built = false;
	public bool $dump_upgrade_built = false;
	public int $ap_cost = 1;

    public int $defense = 0;
}