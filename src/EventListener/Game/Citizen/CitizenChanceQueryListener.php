<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\Citizen;
use App\Entity\CitizenWatch;
use App\Entity\RuinZonePrototype;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Enum\Configuration\TownSetting;
use App\Enum\ScavengingActionType;
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryDigChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenQueryDigChancesEvent::class, method: 'getDigChances', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchDeathChancesEvent::class, method: 'getNightWatchDeathChances', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchDefenseEvent::class, method: 'getNightWatchDefenses', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchInfoEvent::class, method: 'getNightWatchInfo', priority: 0)]
final class CitizenChanceQueryListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            CitizenHandler::class,
            InventoryHandler::class,
            TownHandler::class,
            UserHandler::class,
            EntityManagerInterface::class,
            EventProxyService::class
        ];
    }

	public function getNightWatchDeathChances(CitizenQueryNightwatchDeathChancesEvent $event): void {
		$citizen = $event->data->citizen;
		/** @var CitizenHandler $citizen_handler */
		$citizen_handler = $this->container->get(CitizenHandler::class);
		/** @var UserHandler $user_handler */
		$user_handler = $this->container->get(UserHandler::class);
		/** @var EntityManagerInterface $em */
		$em = $this->container->get(EntityManagerInterface::class);

		$fatigue = $citizen_handler->getNightwatchBaseFatigue($citizen);
		$is_pro = ($citizen->getProfession()->getHeroic() && $user_handler->hasSkill($citizen->getUser(), 'prowatch'));

		for($i = 1 ; $i <= $citizen->getTown()->getDay() - ($event->during_attack ? 2 : 1); $i++){
			/** @var CitizenWatch|null $previousWatches */
			$previousWatches = $em->getRepository(CitizenWatch::class)->findWatchOfCitizenForADay($citizen, $i);
			if ($previousWatches === null || $previousWatches->getSkipped())
				$fatigue = max($citizen_handler->getNightwatchBaseFatigue($citizen), $fatigue - ($is_pro ? 0.025 : 0.05));
			else
				$fatigue += ($is_pro ? 0.05 : 0.1);
		}

		$chances = max($citizen_handler->getNightwatchBaseFatigue($citizen), $fatigue);
		foreach ($citizen->getStatus() as $status)
			$chances += $status->getNightWatchDeathChancePenalty();
		if($citizen->hasRole('ghoul')) $chances -= 0.05;
		$event->deathChance = max(0.0, min($chances, 1.0));
		$event->woundChance = $event->terrorChance = max(0.0, min($chances + $event->townConfig->get(TownConf::CONF_MODIFIER_WOUND_TERROR_PENALTY, 0.05), 1.0)) / 2;

		$event->hintSentence = T::__('Und übrigens, uns erscheint die Idee ganz gut dir noch zu sagen, dass du heute zu einer Wahrscheinlichkeit von {deathChance}% sterben und zu einer Wahrscheinlichkeit von {woundAndTerrorChance}% eine Verwundung oder Angststarre während der Wache erleiden wirst.', 'game');
	}

	public function getNightWatchDefenses(CitizenQueryNightwatchDefenseEvent $event): void {
		$citizen = $event->data->citizen;
		/** @var EventProxyService $events */
		$events = $this->container->get(EventProxyService::class);

		$def = 10 + $citizen->getProfession()->getNightwatchDefenseBonus();

		foreach ($citizen->getStatus() as $status) {
			$def += $status->getNightWatchDefenseBonus();
		}

		foreach ($citizen->getInventory()->getItems() as $item) {
			$itemDef = $events->buildingQueryNightwatchDefenseBonus($citizen->getTown(), $item);;
			$def += $itemDef;
		}

		$event->data->nightwatchDefense += $def;
	}

	public function getNightWatchInfo(CitizenQueryNightwatchInfoEvent $event): void {
		$citizen = $event->data->citizen;
		$event->data->nightwatchInfo['citizen'] = $citizen;
		/** @var EventProxyService $events */
		$events = $this->container->get(EventProxyService::class);

		$def = $event->data->nightwatchInfo['def'] ?? 0;

		$def += 10 + $citizen->getProfession()->getNightwatchDefenseBonus();

		foreach ($citizen->getStatus() as $status) {
			if ($status->getNightWatchDefenseBonus() === 0 && $status->getNightWatchDeathChancePenalty() === 0.0) continue;
			$def += $status->getNightWatchDefenseBonus();
			$event->data->nightwatchInfo['status'][$status->getId()] = [
				'icon' => $status->getIcon(),
				'label' => $status->getLabel(),
				'defImpact' => $status->getNightWatchDefenseBonus(),
				'deathImpact' => round($status->getNightWatchDeathChancePenalty() * 100)
			];
		}

		if ($citizen->hasRole('ghoul'))
			$event->data->nightwatchInfo['status']['ghoul'] = array(
				'icon' => 'ghoul',
				'label' => 'Ghul',
				'defImpact' => 0,
				'deathImpact' => -5
			);

		foreach ($citizen->getInventory()->getItems() as $item) {
			$itemDef = $events->buildingQueryNightwatchDefenseBonus($citizen->getTown(), $item);;
			if($itemDef === 0)
				continue;

			$def += $itemDef;

			$event->data->nightwatchInfo['items'][$item->getId()] = array(
				'prototype' => $item->getPrototype(),
				'defImpact' => $itemDef,
				'deathImpact' => -$item->getPrototype()->getWatchimpact()
			);
		}

		$event->data->nightwatchInfo['def'] = $def;
	}

    private function applyNightMalus(Citizen $citizen, float $malus, int $zoneDistance): float {

        if ($citizen->hasStatus('tg_novlamps')) {
            // Night mode is active, but so are the Novelty Lamps; we must check if they apply
            $novelty_lamps = $this->getService(TownHandler::class)->getBuilding( $citizen->getTown(), 'small_novlamps_#00', true );

            if (
                !$novelty_lamps ||
                ($novelty_lamps->getLevel() === 0 && $zoneDistance >   2) ||
                ($novelty_lamps->getLevel() === 1 && $zoneDistance >   6) ||
                ($novelty_lamps->getLevel() === 2 && $zoneDistance > 999)
            ) return $malus;

            // NovLamps are in effect, no malus
            return 0;

        } else return $malus; // Night mode is active; apply malus
    }

    public function getDigChances(CitizenQueryDigChancesEvent $event): void {

        $base_night_malus = $event->at_night ? match ($event->type) {
            ScavengingActionType::Dig, ScavengingActionType::Scavenge => 0.1,
            ScavengingActionType::DigExploration => 0,
        } : 0;

        // If there are items that prevent night mode present, the night malus is set to a quarter of the base malus
        if ($base_night_malus > 0 && $event->zone && $this->getService(InventoryHandler::class)->countSpecificItems($event->zone->getFloor(), 'prevent_night', true ))
            $base_night_malus /= 4;

        switch ($event->type) {
            case ScavengingActionType::Dig:
                // We're digging on a regular zone

                // A depleted zone have 35% chance of giving an item
                // A non-depleted one have 60% chance of giving an item, + the profession bonus
                $chance = ($event->empty)
                    ? $event->townConfig->get(TownConf::CONF_DIG_CHANCES_DEPLETED, 0.35)
                    : ($event->townConfig->get(TownConf::CONF_DIG_CHANCES_BASE, 0.60) + $event->citizen->getProfession()->getDigBonus());

                // We apply the night malus
                $chance -= $this->applyNightMalus( $event->citizen, $base_night_malus, $event->distance );

                // A depleted zone does not take into account the statuses
                if (!$event->empty) {
                    if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'camper' )) $chance += 0.1;
                    if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'wound5' )) $chance *= 0.5;
                    if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'drunk'  )) $chance -= 0.2;
                }

                $event->chance = $chance;

                break;

            case ScavengingActionType::DigExploration:
                // We're searching an e-ruin
                // TODO: Re-implement how items are generated in an e-ruin

                $digs = ($event->ruinZone?->getDigs() ?? 0) + 1;
                $chance = 1.0 / ( 1.0 + ( $digs / max( 1, $event->townConfig->get(TownSetting::ERuinItemFillrate) - ($digs/3.0) ) ) ) + $event->citizen->getProfession()->getDigBonus();
                //$chance = $event->townConfig->get(TownConf::CONF_EXPLORABLES_DIG_CHANCE, 0.55) + $event->citizen->getProfession()->getDigBonus();

                if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'wound5' )) $chance -= 0.2;
                if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'drunk'  )) $chance -= 0.2;

                $event->chance = $chance;
                break;

            case ScavengingActionType::Scavenge:
                // We're searching a building
                $chance = 1.0 - ($event->prototype?->getEmptyDropChance() ?? 0.25) + $event->citizen->getProfession()->getDigBonus();

                if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'wound5' )) $chance -= 0.2;
                if ($this->getService(CitizenHandler::class)->hasStatusEffect( $event->citizen, 'drunk'  )) $chance -= 0.2;

                // We apply the night malus
                $chance -= $this->applyNightMalus( $event->citizen, $base_night_malus, $event->distance );

                $event->chance = $chance;
                break;
        }

        $event->chance = min(max(0, $event->chance), 1.0);
    }
}