<?php


namespace App\EventListener\Maintenance\ContentMigrations;

use App\Entity\BuildingPrototype;
use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TownContentMigrationEvent::class, method: 'handle', priority: 1000)]
class TownContentMigrateBuildingTreeListener extends TownContentMigrationListener
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TownHandler::class,
            EntityManagerInterface::class
        ]);
    }

    protected function getMigrationName(): string {
        return "Migrate construction site tree";
    }

    protected function applies( TownContentMigrationEvent $event ): bool {
        return true;
    }

    private function unlock( TownContentMigrationEvent $event, BuildingPrototype $prototype ): void {
        $all_parents = [$prototype];
        $current_level = $prototype;
        while ($current_level->getParent())
            $all_parents[] = $current_level = $current_level->getParent();

        $th = $this->getService(TownHandler::class);

        foreach ( array_reverse($all_parents) as $parent )
            if (!($th->getBuilding( $event->town, $parent, false ))) {
                $b = $th->addBuilding( $event->town, $parent );
                if (!$b) throw new \Exception("Unable to unlock <fg=green>[{$parent->getId()}]</> <fg=yellow>{$parent->getLabel()}</>");
                $event->debug( "Unlocking <fg=green>[{$parent->getId()}]</> <fg=yellow>{$parent->getLabel()}</>." );
            }
    }

    protected function execute( TownContentMigrationEvent $event ): void {
        $em = $this->getService(EntityManagerInterface::class);
        $th = $this->getService(TownHandler::class);

        foreach ($em->getRepository(BuildingPrototype::class)->findBy(['blueprint' => 0]) as $base_prototype)
            if (!($th->getBuilding( $event->town, $base_prototype, false ))) {
                $event->debug( "Default building <fg=green>[{$base_prototype->getId()}]</> <fg=yellow>{$base_prototype->getLabel()}</> is not unlocked." );
                $this->unlock( $event, $base_prototype );
            }

        do {
            $changed = false;
            foreach ( $event->town->getBuildings() as $building ) {
                if (!($parent = $building->getPrototype()->getParent())) continue;
                if (!($th->getBuilding( $event->town, $parent, false ))) {
                    $event->debug( "Building <fg=green>[{$building->getPrototype()->getId()}]</> <fg=yellow>{$building->getPrototype()->getLabel()}</> (instance <fg=green>{$building->getId()}</>) is missing its parent." );
                    $this->unlock( $event, $parent );

                    $changed = true;
                    break;
                }
            }

        } while ($changed);
    }


}