<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;
use Psr\Log\LoggerInterface;

class CitizenWatchData extends CitizenBaseData
{
    public readonly bool $during_attack;

    public readonly ?LoggerInterface $log;

	/**
	 * @param Citizen $citizen
	 * @param bool $duringAttack
     * @param LoggerInterface $log
	 * @return CitizenWatchData
	 * @noinspection PhpDocSignatureInspection
	 */
	public function setup( Citizen $citizen, bool $duringAttack = false, LoggerInterface $log = null): void {
		parent::setup($citizen);
		$this->during_attack = $duringAttack;
        $this->log = $log;
	}

	public float $deathChance = 0.0;
	public float $woundChance = 0.0;
	public float $terrorChance = 0.0;
	public string $hintSentence = "";
	public int $nightwatchDefense = 0;
	public array $nightwatchInfo = [
		'citizen' => null,
		'def' => 0,
		'bonusDef' => 0,
		'bonusSurvival' => 0,
		'status' => [],
		'items' => []
	];
}