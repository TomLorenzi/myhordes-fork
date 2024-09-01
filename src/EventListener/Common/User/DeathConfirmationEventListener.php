<?php


namespace App\EventListener\Common\User;

use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\TownRankingProxy;
use App\Enum\HeroXPType;
use App\Event\Common\User\DeathConfirmedEvent;
use App\Event\Common\User\DeathConfirmedPostPersistEvent;
use App\Event\Common\User\DeathConfirmedPrePersistEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\DoctrineCacheService;
use App\Service\EventProxyService;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardGenerosity', priority: 0)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardPictos', priority: -100)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardHxp', priority: -150)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardLegacyHxp', priority: -155)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'mutateLastWords', priority: -200)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'persistDeath', priority: -300)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'dispatchPictoUpdateEvent', priority: 0)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'dispatchSoulPointUpdateEvent', priority: -100)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'cleanPersistentProperties', priority: -1000)]
final class DeathConfirmationEventListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            DoctrineCacheService::class,
            EntityManagerInterface::class,
            EventProxyService::class,
            UserHandler::class,
            UserUnlockableService::class
        ];
    }

    public function awardGenerosity(DeathConfirmedEvent $event): void {

        if ($event->death->getGenerosityBonus() > 0 && !$event->death->getDisabled() && !$event->death->getTown()->getDisabled()) {

            $cache = $this->getService(DoctrineCacheService::class);

            $generosity = $cache->getEntityByIdentifier(FeatureUnlockPrototype::class, 'f_share');
            /** @var FeatureUnlock $instance */
            $instance = $this->getService(EntityManagerInterface::class)->getRepository(FeatureUnlock::class)->findBy([
                'user' => $event->user, 'expirationMode' => FeatureUnlock::FeatureExpirationTownCount,
                'prototype' => $cache->getEntityByIdentifier(FeatureUnlockPrototype::class, 'f_share')
            ])[0] ?? null;

            if (!$instance) $instance = (new FeatureUnlock())->setPrototype( $generosity )->setUser( $event->user )
                ->setExpirationMode( FeatureUnlock::FeatureExpirationTownCount )->setTownCount($event->death->getGenerosityBonus());
            else $instance->setTownCount( $instance->getTownCount() + $event->death->getGenerosityBonus() );

            $this->getService(EntityManagerInterface::class)->persist( $instance );
        }

    }

    public function awardPictos(DeathConfirmedEvent $event): void {
        // Here, we delete picto with persisted = 0,
        // and definitively validate picto with persisted = 1
        /** @var Picto[] $pendingPictosOfUser */
        $pendingPictosOfUser = $this->getService(EntityManagerInterface::class)->getRepository(Picto::class)->findPendingByUserAndTown(
            $event->user,
            $event->death->getTown()
        );

        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($pendingPicto->getPersisted() == 0)
                $this->getService(EntityManagerInterface::class)->remove($pendingPicto);
            else {
                $pendingPicto
                    ->setPersisted(2)
                    ->setDisabled(
                        $event->death->hasDisableFlag(CitizenRankingProxy::DISABLE_PICTOS) ||
                        $event->death->getTown()->hasDisableFlag(TownRankingProxy::DISABLE_PICTOS)
                    );
                $this->getService(EntityManagerInterface::class)->persist($pendingPicto);
            }
        }
    }

    public function awardHxp(DeathConfirmedEvent $event): void {
        if ($event->death->getDay() <= 0) return;
        $this->getService(UserUnlockableService::class)
            ->recordHeroicExperience($event->user, HeroXPType::Global, $event->death->getDay(), 'hxp_survived_days_base', null, [
                'town' => $event->death->getTown()->getName(),
                'days' => $event->death->getDay()
            ], $event->death->getTown(), $event->death, $event->death->getTown()->getSeason());
    }

    public function awardLegacyHxp(DeathConfirmedEvent $event): void {
        if ($event->death->getDay() <= 0) return;
        $this->getService(UserUnlockableService::class)->setLegacyHeroDaysSpent( $event->user, false, $event->death->getDay(), true);
    }

    public function mutateLastWords(DeathConfirmedEvent $event): void {
        $murderDeathTypes = [
            CauseOfDeath::Poison,
            CauseOfDeath::GhulEaten
        ];

        if (!in_array($event->death->getCod()->getRef(), $murderDeathTypes))
            $event->lastWords = str_replace(['{','}'], ['(',')'], $event->lastWords);
        else $event->lastWords = '{gotKilled}';
    }

    public function persistDeath(DeathConfirmedEvent $event): void {
        if ($citizen = $event->death->getCitizen()) {
            $citizen->setActive(false);
            $citizen->setLastWords( $event->lastWords);
            CitizenRankingProxy::fromCitizen( $citizen, true );
            $this->getService(EntityManagerInterface::class)->persist( $citizen );
        }

        $event->death->setConfirmed(true)->setLastWords( $event->lastWords );
    }

    public function dispatchPictoUpdateEvent(DeathConfirmedEvent $event): void {
        $this->getService(EventProxyService::class)->pictosPersisted( $event->user, $event->death->getTown()->getSeason() );
    }

    public function dispatchSoulPointUpdateEvent(DeathConfirmedEvent $event): void {
        // Update soul points
        $event->user->setSoulPoints( $this->getService(UserHandler::class)->fetchSoulPoints( $event->user, false ) );
    }

    public function cleanPersistentProperties(DeathConfirmedEvent $event): void {
        $event->death->setData(null);
    }
}