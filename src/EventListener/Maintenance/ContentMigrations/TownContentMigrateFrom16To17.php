<?php


namespace App\EventListener\Maintenance\ContentMigrations;

use App\Enum\Configuration\CitizenProperties;
use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\Service\EventProxyService;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use App\Traits\System\PrimeInfo;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TownContentMigrationEvent::class, method: 'handle', priority: 800 - 17)]
class TownContentMigrateFrom16To17 extends TownContentMigrateBuildingTreeListener
{
    use PrimeInfo;

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventProxyService::class,
            UserUnlockableService::class,
            UserHandler::class
        ]);
    }

    protected function getMigrationName(): string {
        return "Prime 3.0 Migrations";
    }

    protected function applies( TownContentMigrationEvent $event ): bool {
        return self::primePackageVersionIdentifierSatisfies( $event->town->getPrime(), '^2.0.0', package: 'myhordes/prime-csc' );
    }


    protected function execute( TownContentMigrationEvent $event ): void {

        $em = $this->getService(EntityManagerInterface::class);

        foreach ($event->town->getCitizens() as $citizen)
            if ($citizen->getAlive() && $citizen->getProfession()->getName() === 'hunter') {
                $event->debug( "Citizen <fg=yellow>{$citizen->getName()}</> requires <bg=green>exploration points</> to be set." );
                $em->persist( $citizen->setSp(2) );
            }

        foreach ($event->town->getCitizens() as $citizen)
            if ($citizen->getAlive() && $citizen->getProfession()->getHeroic()) {
                $event->debug( "Setting skill properties for <fg=yellow>{$citizen->getName()}</>..." );

                $citizenPropConfig = [];
                $ids = [];
                $skills = $this->getService(UserUnlockableService::class)->getUnlockedLegacyHeroicPowersByUser( $citizen->getUser() );
                foreach ($skills as $skill) {

                    if ($feature = $skill->getInhibitedBy()) {
                        if ($this->getService(UserHandler::class)->checkFeatureUnlock( $citizen->getUser(), $feature, false ) )
                            continue;
                    }

                    $ids[] = $skill->getId();
                    $event->debug( "Adding properties for skill <fg=yellow>#{$skill->getId()} {$skill->getName()}</>..." );

                    foreach ($skill->getCitizenProperties() ?? [] as $propPath => $value)
                        Arr::set(
                            $citizenPropConfig,
                            $propPath,
                            CitizenProperties::from($propPath)->merge(Arr::get(
                                $citizenPropConfig,
                                $propPath
                            ), $value));
                }

                Arr::set( $citizenPropConfig, CitizenProperties::ActiveSkillIDs->value, $ids );
                $citizen->setProperties( ($citizen->getProperties() ?? new \App\Entity\CitizenProperties())->setProps( $citizenPropConfig ) );
                $em->persist( $citizen );
                $em->persist( $citizen->getProperties() );
            }

        $event->town->setPrime( self::buildPrimePackageVersionIdentifier(package: 'myhordes/prime-csc', version: '3.0.0.0') );
    }
}