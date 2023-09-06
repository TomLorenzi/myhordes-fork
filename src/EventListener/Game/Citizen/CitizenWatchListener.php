<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\CitizenWatch;
use App\Event\Game\Citizen\CitizenQueryDeathChancesEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenQueryDeathChancesEvent::class, method: 'getDeathChances', priority: 0)]
final class CitizenWatchListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

	public function getDeathChances(CitizenQueryDeathChancesEvent $event): void {
		$citizen = $event->citizen;
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
		$event->woundChance = $event->terrorChance = max(0.0, min($chances + $event->townConfig->get(TownConf::CONF_MODIFIER_WOUND_TERROR_PENALTY, 0.05), 1.0));
		$event->hintSentence = T::__('Und übrigens, uns erscheint die Idee ganz gut dir noch zu sagen, dass du heute zu einer Wahrscheinlichkeit von {deathChance}% sterben und zu einer Wahrscheinlichkeit von {woundAndTerrorChance}% eine Verwundung oder Angststarre während der Wache erleiden wirst.', 'game');
	}

    public static function getSubscribedServices(): array
    {
        return [
            CitizenHandler::class,
			UserHandler::class,
			EntityManagerInterface::class
        ];
    }
}