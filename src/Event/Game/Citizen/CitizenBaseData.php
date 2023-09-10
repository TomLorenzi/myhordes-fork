<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;

class CitizenBaseData
{

	public readonly Citizen $citizen;

	/**
	 * @param Citizen $citizen
	 * @return CitizenBaseData
	 * @noinspection PhpDocSignatureInspection
	 */
	public function setup( Citizen $citizen): void {
		$this->citizen = $citizen;
	}

}