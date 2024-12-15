<?php

namespace MyHordes\Prime\Helpers;

use App\Entity\Citizen;
use App\Entity\LogEntryTemplate;
use App\Entity\TownLogEntry;
use App\Service\LogTemplateHandler;
use DateTime;

class PrimeLogTemplateHandler extends LogTemplateHandler {

	public function dumpItemsRecover(Citizen $citizen, $items, int $defense): TownLogEntry {
		$variables = [
			'citizen' => $citizen->getId(),
			'items' => array_map( function($e) { if(array_key_exists('count', $e)) {return array('id' => $e['item']->getId(),'count' => $e['count']);} else { return array('id' => $e[0]->getId()); } }, $items ),
			'def' => $defense
		];
		$template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'dumpItemsRecover']);

		return (new TownLogEntry())
			->setLogEntryTemplate($template)
			->setVariables($variables)
			->setTown( $citizen->getTown() )
			->setDay( $citizen->getTown()->getDay() )
			->setTimestamp( new DateTime('now') )
			->setCitizen( $citizen );
	}

	public function citizenDisposalBurn(Citizen $actor, Citizen $disposed, bool $hasGarden = false): TownLogEntry {
		$variables = array('citizen' => $actor->getId(), 'disposed' => $disposed->getId());
		$template = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $hasGarden ? 'citizenDisposalBurnGarden' : 'citizenDisposalBurn']);

		return (new TownLogEntry())
			->setLogEntryTemplate($template)
			->setVariables($variables)
			->setTown( $actor->getTown() )
			->setDay( $actor->getTown()->getDay() )
			->setCitizen( $actor )
			->setSecondaryCitizen( $disposed )
			->setTimestamp( new DateTime('now') );
	}
}