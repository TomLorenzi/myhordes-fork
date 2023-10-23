<?php


namespace App\EventListener\Maintenance\ContentMigrations;

use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\EventListener\ContainerTypeTrait;
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

        foreach ($event->town->getBuildings() as $building) {

            if ($building->getAp() > $building->getPrototype()->getAp()) {
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> AP ({$building->getAp()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$building->getPrototype()->getAp()}</>" );
                $em->persist($building->setAp( $building->getPrototype()->getAp() ) );
            }

            if ($building->getHp() > $building->getPrototype()->getHp()) {
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> HP ({$building->getHp()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$building->getPrototype()->getHp()}</>" );
                $em->persist($building->setHp( $building->getPrototype()->getHp() ) );
            }

            if ($building->getLevel() > $building->getPrototype()->getMaxLevel()) {
                $event->debug( "Clamping building <fg=green>{$building->getId()}</> vote level ({$building->getLevel()}) to prototype <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> value of <fg=green>{$building->getPrototype()->getMaxLevel()}</>" );
                $em->persist($building->setLevel( $building->getPrototype()->getMaxLevel() ) );
            }

        }
    }


}