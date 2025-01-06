<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\ZombieEstimation;
use App\Event\Game\Town\Basic\Buildings\BuildingDestroyedDuringAttackPostEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingDestructionEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\DeathHandler;
use App\Service\GameProfilerService;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingDestructionEvent::class, method: 'onSetUpBuildingInstance', priority: 0)]
#[AsEventListener(event: BuildingDestructionEvent::class, method: 'onStoreInGPS', priority: -1)]
#[AsEventListener(event: BuildingDestructionEvent::class, method: 'onExecuteSpecialEffect', priority: -105)]
#[AsEventListener(event: BuildingDestroyedDuringAttackPostEvent::class, method: 'onProcessPostAttackDestructionEffect', priority: 1)]
final class BuildingDestructionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            DeathHandler::class,
            TownHandler::class,
            GameProfilerService::class,
            CitizenHandler::class
        ];
    }

    public function onSetUpBuildingInstance( BuildingDestructionEvent $event ): void {
        $this->getService(TownHandler::class)->destroy_building($event->town, $event->building);
        $event->markModified();
    }

    public function onStoreInGPS( BuildingDestructionEvent $event ): void {
        $this->getService(GameProfilerService::class)->recordBuildingDestroyed( $event->building->getPrototype(), $event->town, $event->method );
        $event->markModified();
    }

    public function onExecuteSpecialEffect( BuildingDestructionEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {
            case 'small_arma_#00':
                $gazette = $event->town->findGazette( $event->town->getDay(), true );
                $cod = $this->getService(EntityManagerInterface::class)->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Radiations]);

                // It is destroyed, let's kill everyone with the good cause of death
                foreach ($this->getService(TownHandler::class)->get_alive_citizens($event->town) as $citizen) {
                    $gazette->setDeaths($gazette->getDeaths() + 1);
                    $this->getService(DeathHandler::class)->kill($citizen,$cod,$rr);
                    foreach ($rr as $r) $this->getService(EntityManagerInterface::class)->remove($r);
                }

                $gazette->setReactorExplosion(true);
                $this->getService(EntityManagerInterface::class)->persist($gazette);
                break;

            case 'small_fireworks_#00':
                $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->fireworkExplosion($event->town, $event->building->getPrototype()));

                // Fetching alive citizens
                $citizens = $this->getService(TownHandler::class)->get_alive_citizens($event->town);
                $toInfect = [];
                // Keeping citizens in town
                foreach ($citizens as $citizen) {
                    /** @var Citizen $citizen */
                    if (
                        $citizen->getZone() ||
                        !$citizen->getAlive() ||
                        $citizen->hasStatus('infected') ||
                        $citizen->hasRole('ghoul')
                    ) continue;
                    $toInfect[] = $citizen;
                }

                // Randomness
                shuffle($toInfect);
                // We infect the first half of the list
                for ($i=0; $i < count($toInfect) / 2; $i++)
                    $this->getService(CitizenHandler::class)->inflictStatus($toInfect[$i], "tg_meta_ginfect", true);

                $ratio = 1 - mt_rand(13, 16) / 100;

                $gazette = $event->town->findGazette( $event->town->getDay(), true );
                $gazette->setFireworksExplosion(true);
                $this->getService(EntityManagerInterface::class)->persist($gazette);

                /** @var ZombieEstimation $est */
                $est = $this->getService(EntityManagerInterface::class)->getRepository(ZombieEstimation::class)->findOneByTown($event->town,$event->town->getDay());

                $zombie_diff = $est->getZombies() - ($est->getZombies() * $ratio);
                $est->setZombies($est->getZombies() * $ratio);
                $est->setTargetMin($est->getTargetMin() - $zombie_diff);
                $est->setTargetMax($est->getTargetMax() - $zombie_diff);
                $this->getService(EntityManagerInterface::class)->persist($est);
                break;

            default: break;
        }
    }

    public function onProcessPostAttackDestructionEffect( BuildingDestroyedDuringAttackPostEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {

            // Fireworks destruction adds +300 temp defense for the next day
            case 'small_fireworks_#00':
                $event->town->setTempDefenseBonus( $event->town->getTempDefenseBonus() + 300 );

            default: break;
        }
    }

}