<?php


namespace MyHordes\Prime\EventListener\Maintenance;

use App\Entity\BuildingPrototype;
use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\EventListener\Maintenance\ContentMigrations\TownContentMigrateBuildingTreeListener;
use App\Service\EventProxyService;
use App\Service\TownHandler;
use App\Traits\System\PrimeInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TownContentMigrationEvent::class, method: 'handle', priority: 800 - 16)]
class TownContentMigrateFrom15To16 extends TownContentMigrateBuildingTreeListener
{
    use PrimeInfo;

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventProxyService::class
        ]);
    }

    protected function getMigrationName(): string {
        return "Prime 2.0 Migrations";
    }

    protected function applies( TownContentMigrationEvent $event ): bool {
        return self::primePackageVersionIdentifierSatisfies( $event->town->getPrime(), '^1.0.0', match_shim: true );
    }

    protected function execute( TownContentMigrationEvent $event ): void {

        $em = $this->getService(EntityManagerInterface::class);
        $th = $this->getService(TownHandler::class);

        // Transfer watchtower vote level
        $wt = $em->getRepository( BuildingPrototype::class )->findOneByName( 'item_tagger_#00' );
        $wt_vote_level = $th->getBuilding( $event->town, $wt, true )?->getLevel() ?? 0;
        if ($wt_vote_level > 0) {
            $event->manually_distributed_votes = $event->manually_distributed_votes + $wt_vote_level;
            $event->debug( "The <fg=yellow>{$wt->getLabel()}</> has been voted to level <fg=green>{$wt_vote_level}</>." );

            $outlook = $th->getBuilding( $event->town, 'item_scope_#00' ) ?? $this->unlock( $event, $em->getRepository( BuildingPrototype::class )->findOneByName( 'item_scope_#00' ) );
            if (!$outlook->getComplete()) {
                $event->debug("Completing construction of <fg=yellow>{$outlook->getPrototype()->getLabel()}</>.");
                $this->getService(EventProxyService::class)->buildingConstruction( $outlook, 'migration-s15-s16' );
            }

            while ($outlook->getLevel() < min($outlook->getPrototype()->getMaxLevel(), $wt_vote_level)) {
                $outlook->setLevel( $outlook->getLevel() + 1 );
                $event->debug("Upgrading <fg=yellow>{$outlook->getPrototype()->getLabel()}</> to level <fg=green>{$outlook->getLevel()}</>.");
                $this->getService(EventProxyService::class)->buildingUpgrade($outlook, true);
                $this->getService(EventProxyService::class)->buildingUpgrade($outlook, false);
            }

        }

        // Mitigate dump status
        $base_dump = $th->getBuilding( $event->town, 'small_trash_#00', true );
        if ($base_dump) {
            $resource_dump = $th->getBuilding( $event->town, 'small_trash_#01', true ) ?? $th->getBuilding( $event->town, 'small_trash_#02', true ) ?? null;
            $animal_dump   = $th->getBuilding( $event->town, 'small_trash_#06', true ) ?? $th->getBuilding( $event->town, 'small_howlingbait_#00', true ) ?? null;
            $food_def_dump = $th->getBuilding( $event->town, 'small_trash_#03', true ) ?? $th->getBuilding( $event->town, 'small_trash_#04', true ) ?? $th->getBuilding( $event->town, 'small_trash_#05', true ) ?? null;

            $needed_dump_vote_level = 0;
            if ($resource_dump) {
                $event->debug("Has <fg=yellow>{$resource_dump->getPrototype()->getLabel()}</>, dump level must be set to <fg=green>3</>.");
                $needed_dump_vote_level = 3;
            } elseif ( $animal_dump ) {
                $event->debug("Has <fg=yellow>{$animal_dump->getPrototype()->getLabel()}</>, dump level must be set to <fg=green>2</>.");
                $needed_dump_vote_level = 2;
            } elseif ( $food_def_dump ) {
                $event->debug("Has <fg=yellow>{$food_def_dump->getPrototype()->getLabel()}</>, dump level must be set to <fg=green>1</>.");
                $needed_dump_vote_level = 1;
            }

            if ($needed_dump_vote_level > 0) $event->manually_distributed_votes = $event->manually_distributed_votes + $needed_dump_vote_level;

            while ($base_dump->getLevel() < min($base_dump->getPrototype()->getMaxLevel(), $needed_dump_vote_level)) {
                $em->persist( $base_dump->setLevel( $base_dump->getLevel() + 1 ) );
                $event->debug("Upgrading <fg=yellow>{$base_dump->getPrototype()->getLabel()}</> to level <fg=green>{$base_dump->getLevel()}</>.");
                $this->getService(EventProxyService::class)->buildingUpgrade($base_dump, true);
                $this->getService(EventProxyService::class)->buildingUpgrade($base_dump, false);
            }
        }

        $event->town->setPrime( self::buildPrimePackageVersionIdentifier(version: '2.0.0.0') );

    }


}