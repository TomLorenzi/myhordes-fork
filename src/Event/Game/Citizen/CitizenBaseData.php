<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;

class CitizenBaseData
{

	public readonly Citizen $citizen;

	/**
	 * @param Citizen $citizen
	 * @param bool $duringAttack
	 * @return CitizenBaseData
	 * @noinspection PhpDocSignatureInspection
	 */
	public function setup( Citizen $citizen, bool $duringAttack = false): void {
		$this->citizen = $citizen;
		$this->during_attack = $duringAttack;
	}
	public readonly bool $during_attack;
	public float $deathChance = 0.0;
	public float $woundOrTerrorChance = 0.0;

}