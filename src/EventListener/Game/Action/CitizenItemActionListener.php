<?php


namespace App\EventListener\Game\Action;

use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use App\Entity\Zone;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class CitizenItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            ZoneHandler::class,
            TownHandler::class,
        ];
    }

    
    
    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {
            // Banned citizen note
            case 15: {
                $zones = $this->getService(ZoneHandler::class)->getZoneWithHiddenItems($event->citizen->getTown());
                usort( $zones, fn(Zone $a, Zone $b) => $b->getItemsHiddenAt() <=> $a->getItemsHiddenAt() );
                if(count($zones) > 0) {
                    $zone = $zones[0];
                    $event->cache->addTag('bannote_ok');
                    $event->cache->setTargetZone($zone);
                } else {
                    $event->cache->addTag('bannote_fail');
                }
                break;
            }

            // Vote for a role
            case 18:case 19: {
            $role_name = "";
            switch($event->type){
                case 18:
                    $role_name = "shaman";
                    break;
                case 19:
                    $role_name = "guide";
                    break;
            }

            if (!is_a( $event->target, Citizen::class )) break;

            if(!$event->target->getAlive()) break;

            $role = $this->getService(EntityManagerInterface::class)->getRepository(CitizenRole::class)->findOneBy(['name' => $role_name]);
            if(!$role) break;

            if ($this->getService(EntityManagerInterface::class)->getRepository(CitizenVote::class)->findOneByCitizenAndRole($event->citizen, $role))
                break;

            if (!$this->getService(TownHandler::class)->is_vote_needed($event->citizen->getTown(), $role)) break;

            // Add our vote !
            $citizenVote = (new CitizenVote())
                ->setAutor($event->citizen)
                ->setVotedCitizen($event->target)
                ->setRole($role);

            $event->citizen->addVote($citizenVote);

            // Persist
            $this->getService(EntityManagerInterface::class)->persist($citizenVote);
            $this->getService(EntityManagerInterface::class)->persist($event->citizen);

            break;
        }
        }
    }

}