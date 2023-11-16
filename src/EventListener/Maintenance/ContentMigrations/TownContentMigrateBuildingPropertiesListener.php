<?php


namespace App\EventListener\Maintenance\ContentMigrations;

use App\Entity\Building;
use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\EventProxyService;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TownContentMigrationEvent::class, method: 'handle', priority: 500)]
class TownContentMigrateBuildingPropertiesListener extends TownContentMigrationListener
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EntityManagerInterface::class,
            RandomGenerator::class,
            EventProxyService::class
        ]);
    }


    protected function getMigrationName(): string {
        return "Migrate construction properties";
    }

    protected function applies( TownContentMigrationEvent $event ): bool {
        return true;
    }

    protected function execute( TownContentMigrationEvent $event ): void {
        $em = $this->getService(EntityManagerInterface::class);

        $total_vote_clamp = 0;

        foreach ($event->town->getBuildings() as $building) {

            $ap = $building->getPrototype()->getAp();
            if ($building->getAp() > $ap) {
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> AP ({$building->getAp()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$ap}</>" );
                $em->persist($building->setAp( $building->getPrototype()->getAp() ) );
            }

            $hp = $building->getPrototype()->getHp() ?: $building->getPrototype()->getAp();
            if ($building->getHp() > $hp) {
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> HP ({$building->getHp()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$hp}</>" );
                $em->persist($building->setHp( $building->getPrototype()->getHp() ) );
            }

            if ($building->getLevel() > $building->getPrototype()->getMaxLevel()) {
                $total_vote_clamp += $building->getLevel() - $building->getPrototype()->getMaxLevel();
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> vote level ({$building->getLevel()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$building->getPrototype()->getMaxLevel()}</>" );
                $em->persist($building->setLevel( $building->getPrototype()->getMaxLevel() ) );
            }

        }

        $total_vote_clamp -= $event->manually_distributed_votes;
        if ($total_vote_clamp > 0) {
            $event->debug( "Town lost a total of <fg=green>$total_vote_clamp</> daily votes. Compensating by randomly increasing the vote level of other buildings." );

            $building_list = $event->town->getBuildings()->filter( fn(Building $b) => $b->getComplete() && $b->getLevel() < $b->getPrototype()->getMaxLevel() )->toArray();
            while (!empty($building_list) && $total_vote_clamp > 0) {
                /** @var Building $building */
                $building = $this->getService(RandomGenerator::class)->pick( $building_list );
                $em->persist( $building->setLevel( $building->getLevel() + 1 ) );
                $event->debug("Upgrading <fg=yellow>{$building->getPrototype()->getLabel()}</> to level <fg=green>{$building->getLevel()}</>.");
                $this->getService(EventProxyService::class)->buildingUpgrade($building, true);
                $this->getService(EventProxyService::class)->buildingUpgrade($building, false);

                $total_vote_clamp--;
                $building_list = array_filter( $building_list, fn(Building $b) => $b->getLevel() < $b->getPrototype()->getMaxLevel() );
            }

            if ($total_vote_clamp > 0) $event->debug( "No further buildings available to distribute the remaining <fg=green>$total_vote_clamp</> daily votes to." );
        }
    }


}