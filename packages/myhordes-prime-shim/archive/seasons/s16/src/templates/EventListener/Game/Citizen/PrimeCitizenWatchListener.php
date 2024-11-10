<?php

namespace MyHordes\Prime\EventListener\Game\Citizen;

use App\Entity\{ActionCounter, CitizenWatch};
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Citizen\CitizenChanceQueryListener;
use Doctrine\Common\Collections\Criteria;
use App\Service\{EventProxyService, TownHandler, UserHandler};
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CitizenQueryNightwatchDeathChancesEvent::class, method: 'getNightWatchDeathChances', priority: 10)]
#[AsEventListener(event: CitizenQueryNightwatchDefenseEvent::class, method: 'getNightWatchDefenses', priority: 10)]
#[AsEventListener(event: CitizenQueryNightwatchInfoEvent::class, method: 'getNightWatchInfo', priority: 10)]
final class PrimeCitizenWatchListener implements ServiceSubscriberInterface {
	use ContainerTypeTrait;

	public function __construct(
		private readonly ContainerInterface $container,
	) {}

	public function getNightWatchDeathChances(CitizenQueryNightwatchDeathChancesEvent $event): void {
		$event->skipPropagationTo( CitizenChanceQueryListener::class, 'getNightWatchDeathChances' );

		$citizen = $event->data->citizen;
		/** @var UserHandler $user_handler */
		$user_handler = $this->container->get(UserHandler::class);
		/** @var TownHandler $town_handler */
		$town_handler = $this->container->get(TownHandler::class);
		/** @var EntityManagerInterface $em */
		$em = $this->container->get(EntityManagerInterface::class);
		/** @var TranslatorInterface $trans */
		$trans = $this->container->get(TranslatorInterface::class);

		$is_pro = ($citizen->getProfession()->getHeroic() && $user_handler->hasSkill($citizen->getUser(), 'prowatch'));

		$chances = 0.08;
		if ($citizen->getProfession()->getName() === "guardian")
			$chances = 0.03;
		else if ($citizen->getProfession()->getName() === "tamer" && $town_handler->getBuilding($citizen->getTown(), "item_tamed_pet_#00"))
			$chances = 0.05;
		$minChances = $chances;

		$criteria = new Criteria();
		$criteria->andWhere($criteria->expr()->eq('citizen', $citizen));
		$criteria->andWhere($criteria->expr()->lt('day', $citizen->getTown()->getDay()));

		$previousWatches = count($em->getRepository(CitizenWatch::class)->matching($criteria));
		if ($is_pro)
			$watchMap = [0, 0.01, 0.04, 0.09, 0.15, 0.20, 0.30, 0.40, 0.50, 0.60, 0.75, 0.90];
		else
			$watchMap = [0, 0.01, 0.04, 0.09, 0.20, 0.30, 0.42, 0.56, 0.72, 0.90];

		$chances += $watchMap[min($previousWatches, count($watchMap))];

		$event->deathChance = round(max(0.0, min($chances, 1.0)),2);
		$woundRatio = ($citizen->getTown()->getType()->getName() == "panda" ? 0.4 : 0.2);
		$terrorRatio = ($citizen->getTown()->getType()->getName() == "panda" ? 0.3 : 0.1);
		$event->woundChance = round(max(0.0, min(1 - ((1-$chances)-(1-$chances)*$woundRatio), 1.0)),2);
		$event->terrorChance = round(max(0.0, min(1 - ((1-$chances)-(1-$chances)*$terrorRatio), 1.0)),2);

		// The items
		$items_impact = [];
		foreach ($citizen->getInventory()->getItems() as $item) {
			$itemImpact = $item->getPrototype()->getWatchimpact();
			if ($itemImpact === 0) continue;
			$items_impact[$item->getPrototype()->getName()] = [
				'name' => $item->getPrototype()->getName(),
				'impact' => ($item->getPrototype()->hasProperty('nw_impact_cumul') ? ($items_impact[$item->getPrototype()->getName()]['impact'] ?? 0) + $itemImpact/100.0 : $itemImpact/100.0)
			];
		}

		$event->deathChance -= (array_sum(array_column($items_impact, 'impact')));

		// Previous Bath
		$nbBath = $citizen->getSpecificActionCounterValue(ActionCounter::ActionTypePool);
		if ($event->deathChance > $minChances)
			$event->deathChance = max($minChances, $event->deathChance - ($nbBath * 0.01)); // Each bath gives 1% chance, but it's capped to the base value of the job

		// The statuses
		foreach ($citizen->getStatus() as $status)
			$event->deathChance += $status->getNightWatchDeathChancePenalty();

		// Gas gun
		if ($town_handler->getBuilding($citizen->getTown(), "small_gazspray_#00"))
			$event->terrorChance += 0.1;

		// Guardroom
		if ($town_handler->getBuilding($citizen->getTown(), "small_watchmen_#02"))
			$event->deathChance -= 0.05;

		// Battlements lvl3
		$roundPath = $town_handler->getBuilding($citizen->getTown(), "small_round_path_#00");
		if ($roundPath && $roundPath->getLevel() === 3)
			$event->deathChance -= 0.01;

		// Automatic sprinklers
		if ($town_handler->getBuilding($citizen->getTown(), "small_sprinkler_#00"))
			$event->deathChance += 0.04;

        // Home shower effect
        if ($event->citizen->hasStatus('tg_home_shower')) {
            $event->woundChance -= 0.025;
            $event->terrorChance -= 0.025;
        }

		$hint = [];
		if (0 <= $event->deathChance && $event->deathChance <= 0.15) {
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

		if (0 < $event->woundChance && $event->woundChance <= 0.25) {
			$hint[] = $trans->trans("Du fühlst dich großartig.", [], 'game');
		} else if (0.25 < $event->woundChance && $event->woundChance < 0.50) {
			$hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du hoffst, dass du keinen Arm oder ein Bein verlierst.", [], 'game');
		} else if (0.50 < $event->woundChance && $event->woundChance < 0.75) {
			$hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du bist vorbereitet, Gliedmaßen zu verlieren, aber hoffentlich nicht deinen Kopf...", [], 'game');
		} else {
			$hint[] = $trans->trans("Du bist eingeschüchtert von der Zahl der Zombies, die du siehst. Du hoffst, dass es noch Verbandszeug in der Bank gibt...", [], 'game');
		}

		if (0 < $event->terrorChance && $event->terrorChance <= 0.25) {
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
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);
		if ($townHandler->getBuilding($citizen->getTown(), "item_tamed_pet_#00")) {
			$impact = ($citizen->getProfession()->getName() === "tamer" ? 15 : 5);
			$event->nightwatchDefense += $impact;
		}
	}
	public function getNightWatchInfo(CitizenQueryNightwatchInfoEvent $event): void {
		$citizen = $event->data->citizen;
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);
		/** @var TranslatorInterface $trans */
		$trans = $this->container->get(TranslatorInterface::class);
		/** @var EventProxyService $events */
		$events = $this->container->get(EventProxyService::class);
		$def = $event->data->nightwatchInfo['def'] ?? 0;
		$event->data->nightwatchInfo['bonusDef'] = $citizen->getProfession()->getNightwatchDefenseBonus();
		$event->data->nightwatchInfo['bonusSurvival'] = $citizen->getProfession()->getNightwatchSurvivalBonus();
		if ($building = $townHandler->getBuilding($citizen->getTown(), "item_tamed_pet_#00")) {
			$impact = ($citizen->getProfession()->getName() === "tamer" ? 15 : 5);
			$def += $impact;
			$event->data->nightwatchInfo['other']['building_tamed_pet_#00'] = [
				'icon' => "building/item_tamed_pet.gif",
				'label' => $trans->trans($building->getPrototype()->getLabel(), [], 'buildings'),
				'defImpact' => $impact,
				'deathImpact' => ($citizen->getProfession()->getName() === "tamer" ? -3 : 0)
			];
		}

		foreach ($citizen->getInventory()->getItems() as $item) {
			$itemDef = $events->buildingQueryNightwatchDefenseBonus($citizen->getTown(), $item);;
			if ($item->getPrototype()->getWatchimpact() === 0 && $itemDef === 0) continue;
			if (isset($event->data->nightwatchInfo['items'][$item->getId()])) {
				if ($item->getPrototype()->hasProperty('nw_impact_cumul') || $event->data->nightwatchInfo['items'][$item->getId()]['deathImpact'] == 0)
					$event->data->nightwatchInfo['items'][$item->getId()]['deathImpact'] = $item->getPrototype()->getWatchimpact();
			} else {
				$event->data->nightwatchInfo['items'][$item->getId()] = array(
					'prototype' => $item->getPrototype(),
					'deathImpact' => -$item->getPrototype()->getWatchimpact(),
					'defImpact' => $itemDef,
				);
			}
		}

		$event->data->nightwatchInfo['def'] = $def;
	}
	public static function getSubscribedServices(): array
	{
		return [
			UserHandler::class,
			EntityManagerInterface::class,
			TownHandler::class,
			TranslatorInterface::class,
			EventProxyService::class
		];
	}
}