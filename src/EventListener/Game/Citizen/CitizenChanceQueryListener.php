<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenWatch;
use App\Entity\RuinZonePrototype;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Configuration\TownSetting;
use App\Enum\EventStages\CitizenValueQuery;
use App\Enum\ScavengingActionType;
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryDigChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\Event\Game\Citizen\CitizenQueryParameterEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CitizenQueryDigChancesEvent::class, method: 'getDigChances', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchDeathChancesEvent::class, method: 'getNightWatchDeathChances', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchDefenseEvent::class, method: 'getNightWatchDefenses', priority: 0)]
#[AsEventListener(event: CitizenQueryNightwatchInfoEvent::class, method: 'getNightWatchInfo', priority: 0)]
#[AsEventListener(event: CitizenQueryParameterEvent::class, method: 'getParameterInfo', priority: 0)]
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
            EventProxyService::class,
            TranslatorInterface::class
        ];
    }

	public function getNightWatchDeathChances(CitizenQueryNightwatchDeathChancesEvent $event): void {
        $citizen = $event->data->citizen;
        $town_handler = $this->getService(TownHandler::class);
        $em = $this->getService(EntityManagerInterface::class);
        $trans = $this->getService(TranslatorInterface::class);

        $log_info = [];

        $log_info['is pro'] =
            ($is_pro = $citizen->property( CitizenProperties::EnableProWatchman ));

        $chances = 0;
        if ($citizen->getProfession()->getName() === "guardian")
            $log_info['death base [guardian]'] = ($chances = 0.03);
        //else if ($citizen->getProfession()->getName() === "tamer" && $town_handler->getBuilding($citizen->getTown(), "small_pet_#00"))
        //    $log_info['death base [tamer & small_pet_#00]'] = ($chances = 0.05);
        else $log_info['death base'] = ($chances = 0.08);

        $minChances = $chances;

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->eq('citizen', $citizen));
        $criteria->andWhere($criteria->expr()->lt('day', $citizen->getTown()->getDay() - ($event->during_attack ? 1 : 0)));

        $log_info['prev watches'] =
            ($previousWatches = count($em->getRepository(CitizenWatch::class)->matching($criteria)));
        if ($is_pro)
            $watchMap = [0, 0.01, 0.04, 0.09, 0.15, 0.20, 0.30, 0.40, 0.50, 0.60, 0.75, 0.90];
        else
            $watchMap = [0, 0.01, 0.04, 0.09, 0.20, 0.30, 0.42, 0.56, 0.72, 0.90];
        $log_info['death fatigue'] =
            ($fatigue = $watchMap[min($previousWatches, count($watchMap))]);

        $chances += $fatigue;

        $event->deathChance = round(max(0.0, min($chances, 1.0)),2);
        $log_info['ratio wound'] = ($woundRatio = ($citizen->getTown()->getType()->getName() == "panda" ? 0.3 : 0.2));
        $log_info['ratio terror'] = ($terrorRatio = ($citizen->getTown()->getType()->getName() == "panda" ? 0.2 : 0.1));
        $event->woundChance = round(max(0.0, min(1 - ((1-$chances)-(1-$chances)*$woundRatio), 1.0)),2);
        $event->terrorChance = round(max(0.0, min(1 - ((1-$chances)-(1-$chances)*$terrorRatio), 1.0)),2);

        $log_info['core chances'] = [
            'death' => $event->deathChance,
            'wound' => $event->woundChance,
            'terror' => $event->terrorChance,
        ];

        // Previous Bath
        $log_info["baths"] = ($nbBath = $citizen->getSpecificActionCounterValue(ActionCounter::ActionTypePool));
        if ($event->deathChance > $minChances) {
            $event->deathChance = max($minChances, $event->deathChance - ($nbBath * 0.01)); // Each bath gives 1% chance, but it's capped to the base value of the job
            $log_info["death after baths"] = round($event->deathChance, 4);;
        }

        // The items
        $items_impact = [];
        foreach ($citizen->getInventory()->getItems() as $item) {
            $itemImpact = $item->getPrototype()->getWatchimpact();
            if ($itemImpact === 0) continue;
            $cumul = $item->getPrototype()->hasProperty('nw_impact_cumul');

            $items_impact[$item->getPrototype()->getName()] = [
                'count' => $cumul ? ($items_impact[$item->getPrototype()->getName()]['count'] ?? 0) + 1 : 1,
                'name' => $item->getPrototype()->getName(),
                'impact' => $cumul ? ($items_impact[$item->getPrototype()->getName()]['impact'] ?? 0) + $itemImpact/100.0 : $itemImpact/100.0
            ];
        }

        foreach ($items_impact as $item => ['impact' => $impact, 'count' => $count])
            $log_info["death item $item x $count"] = -$impact;

        $event->deathChance -= (array_sum(array_column($items_impact, 'impact')));
        if ($citizen->hasRole('ghoul')) $event->deathChance += ($log_info["death ghoul"] = -0.05);

        // The statuses
        foreach ($citizen->getStatus() as $status)
            if (($penalty = $status->getNightWatchDeathChancePenalty()) <> 0) {
                $event->deathChance += $status->getNightWatchDeathChancePenalty();
                $log_info["death status {$status->getName()}"] = $status->getNightWatchDeathChancePenalty();
            }

        // Gas gun
        if ($town_handler->getBuilding($citizen->getTown(), "small_gazspray_#00"))
            $event->terrorChance += ($log_info["terror small_gazspray_#00"] = 0.1);

        // Guardroom
        if ($town_handler->getBuilding($citizen->getTown(), "small_watchmen_#02"))
            $event->deathChance += ($log_info["death small_watchmen_#02"] = -0.05);

        // Battlements lvl3
        $roundPath = $town_handler->getBuilding($citizen->getTown(), "small_round_path_#00");
        if ($roundPath && $roundPath->getLevel() === 3)
            $event->deathChance += ($log_info["death small_round_path_#00"] = -0.01);

        // Automatic sprinklers
        if ($town_handler->getBuilding($citizen->getTown(), "small_sprinkler_#00"))
            $event->deathChance += ($log_info["death small_sprinkler_#00"] = 0.04);

        // Home shower effect
        if ($event->citizen->hasStatus('tg_home_shower')) {
            $event->woundChance += ($log_info["wound tg_home_shower"] = -0.025);
            $event->terrorChance += ($log_info["terror tg_home_shower"] = -0.025);
        }

        if ($citizen->getTown()->getType()->getName() == "panda") {
            if ($event->deathChance >= 0)
                $event->deathChance += $event->deathChance;
            $log_info["death with Hardcore malus"] = round($event->deathChance, 4);
        }

        $bonus = $citizen->property( CitizenProperties::WatchSurvivalBonus );
        if ($bonus !== 0.0) {
            $log_info["watch survival bonus"] = $bonus;
        }
        $event->deathChance -= $bonus;
        $log_info["death with watch survival bonus"] = round($event->deathChance, 4);

        $event->log?->debug( "Calculated night watch chances for citizen <info>{$citizen->getName()}</info>", $log_info );

        // Apply rounding to prevent float imprecision
        $event->deathChance = round($event->deathChance, 4);

        $hint = [];
        if ($event->deathChance <= 0.0) {
            $hint[] = $trans->trans("Auf der Stadtmauer stehend bist du sehr zuversichtlich, was den Ausgang dieses Abends angeht. Du wirst heute nicht sterben!", [], 'game');
        }
        else if ($event->deathChance <= 0.15) {
            $hint[] = $trans->trans("Auf den Zinnen fühlst du dich unbesiegbar. Nichts wird dich heute Nacht zum Wanken bringen.", [], 'game');
        } else if (0.15 < $event->deathChance && $event->deathChance <= 0.30) {
            $hint[] = $trans->trans("Auf den Zinnen fühlst du dich gut. Du willst morgen noch am Leben sein.", [], 'game');
        } else if (0.30 < $event->deathChance && $event->deathChance <= 0.45) {
            $hint[] = $trans->trans("Auf den Zinnen fühlst du dich sicher. Du wartest auf die Zombies, die du bereits herannahen siehst.", [], 'game');
        } else if (0.45 < $event->deathChance && $event->deathChance <= 0.60) {
            $hint[] = $trans->trans("Auf den Zinnen blickst du in den Horizont. Du hoffst, morgen noch einmal die Sonne aufgehen zu sehen.", [], 'game');
        } else if (0.60 < $event->deathChance && $event->deathChance <= 0.75) {
            $hint[] = $trans->trans("Auf den Zinnen fühlst du dich unschlüssig und deine Laune ist schlecht. Vielleicht gibt es kein Morgen für dich...", [], 'game');
        } else if (0.75 < $event->deathChance && $event->deathChance <= 0.90) {
            $hint[] = $trans->trans("Auf den Zinnen bist du zittrig und deine Laune ist am Tiefpunkt. Du weißt nicht, ob du das Licht des Tages erblicken wirst...", [], 'game');
        } else {
            $hint[] = $trans->trans("Auf den Zinnen wird dein Herz von Angst erdrückt. Du spürst, dass dein Leben am seidenen Faden hängt...", [], 'game');
        }

        if ($event->woundChance <= 0.25) {
            $hint[] = $trans->trans("Du fühlst dich großartig.", [], 'game');
        } else if (0.25 < $event->woundChance && $event->woundChance < 0.50) {
            $hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du hoffst, dass du keinen Arm oder ein Bein verlierst.", [], 'game');
        } else if (0.50 < $event->woundChance && $event->woundChance < 0.75) {
            $hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du bist vorbereitet, Gliedmaßen zu verlieren, aber hoffentlich nicht deinen Kopf...", [], 'game');
        } else {
            $hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du hoffst, dass es noch Verbandszeug in der Bank gibt...", [], 'game');
        }

        if ($event->terrorChance <= 0.25) {
            $hint[] = $trans->trans("Selbst wenn du dir vorstellst, was dich heute Abend erwartet, bleibst du zumindest ein wenig gelassen.", [], 'game');
        } else if (0.25 < $event->terrorChance && $event->terrorChance < 0.50) {
            $hint[] = $trans->trans("Du spürst die Aufregung. Es wird eine gruselige Nacht werden.", [], 'game');
        } else if (0.50 < $event->terrorChance && $event->terrorChance < 0.75) {
            $hint[] = $trans->trans("Du versuchst, deine Gedanken positiv zu halten, aber dir kommt nur der Tod in den Sinn... Glücklich! Es wird eine lange Nacht werden.", [], 'game');
        } else {
            $hint[] = $trans->trans("Der Anblick der Zombies, die sich den Festungsmauern nähern, versetzt dich in Panik.", [], 'game');
        }
        $event->hintSentence = implode("<br />", $hint);
	}

	public function getNightWatchDefenses(CitizenQueryNightwatchDefenseEvent $event): void {
		$citizen = $event->data->citizen;
		/** @var EventProxyService $events */
		$events = $this->getService(EventProxyService::class);

		$def = 10 + $citizen->property(CitizenProperties::WatchDefense) + $citizen->getProfession()->getNightwatchDefenseBonus();

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

		$events = $this->getService(EventProxyService::class);

		$def = $event->data->nightwatchInfo['def'] ?? 0;
		$def += 10 + $citizen->property(CitizenProperties::WatchDefense) + $citizen->getProfession()->getNightwatchDefenseBonus();

        $event->data->nightwatchInfo['bonusDef'] = $citizen->getProfession()->getNightwatchDefenseBonus();
        $event->data->nightwatchInfo['bonusSurvival'] = $citizen->getProfession()->getNightwatchSurvivalBonus();

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
			$itemDef = $events->buildingQueryNightwatchDefenseBonus($citizen->getTown(), $item);
            if ($item->getPrototype()->getWatchimpact() === 0 && $itemDef === 0)
				continue;

			$def += $itemDef;

            if (isset($event->data->nightwatchInfo['items'][$item->getId()])) {
                if ($item->getPrototype()->hasProperty('nw_impact_cumul') || $event->data->nightwatchInfo['items'][$item->getId()]['deathImpact'] == 0)
                    $event->data->nightwatchInfo['items'][$item->getId()]['deathImpact'] = $item->getPrototype()->getWatchimpact();
            } else {
                $event->data->nightwatchInfo['items'][$item->getId()] = array(
                    'prototype' => $item->getPrototype(),
                    'defImpact' => $itemDef,
                    'deathImpact' => -$item->getPrototype()->getWatchimpact(),
                );
            }
		}

		$event->data->nightwatchInfo['def'] = $def;
	}

    private function applyNightMalus(Citizen $citizen, float $malus, int $zoneDistance): float {

        if ($citizen->hasStatus('tg_novlamps')) {
            // Night mode is active, but so are the Novelty Lamps; we must check if they apply
            $novelty_lamps = $this->getService(TownHandler::class)->getBuilding( $citizen->getTown(), 'small_novlamps_#00', true );

            if (
                !$novelty_lamps ||
                ($novelty_lamps->getLevel() === 0 && $zoneDistance >   6) ||
                ($novelty_lamps->getLevel() === 1 && $zoneDistance > 999)
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

                $event->chance = $chance + ($event->zone?->getScoutLevel() ?? 0) * 0.025;

                break;

            case ScavengingActionType::DigExploration:
                // We're searching an e-ruin
                $digs = ($event->ruinZone?->getDigs() ?? 0) + 1;
                $chance = (1.0 + $event->citizen->getProfession()->getDigBonus()) / ( 1.0 + ( $digs / max( 1, $event->townConfig->get(TownSetting::ERuinItemFillrate) - ($digs/3.0) ) ) );
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

    public function getParameterInfo(CitizenQueryParameterEvent $event): void {
        switch ($event->query) {
            case CitizenValueQuery::MaxSpExtension:
                $value = $event->value;
                if ($event->citizen->hasStatus('tg_has_bike')) $value += 2;
                if ($event->citizen->hasStatus('tg_has_shoe')) $value += 1;
                $event->value = $value;
                break;
            case CitizenValueQuery::NightlyAttraction:
                $value = $event->value;
                if ($event->citizen->hasStatus('tg_flag')) $value += 0.025;
                $event->value = $value;
                break;
            default:
                break;
        }
    }
}