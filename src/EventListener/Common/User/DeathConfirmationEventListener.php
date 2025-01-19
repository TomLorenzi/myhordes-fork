<?php


namespace App\EventListener\Common\User;

use App\Entity\CauseOfDeath;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Enum\Game\CitizenPersistentCache;
use App\Enum\HeroXPType;
use App\Event\Common\User\DeathConfirmedEvent;
use App\Event\Common\User\DeathConfirmedPostPersistEvent;
use App\Event\Common\User\DeathConfirmedPrePersistEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\DoctrineCacheService;
use App\Service\EventProxyService;
use App\Service\User\PictoService;
use App\Service\User\UserUnlockableService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardGenerosity', priority: 0)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardPictos', priority: -100)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardHxp', priority: -150)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardPrimeHxp', priority: -151)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'awardLegacyHxp', priority: -155)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'mutateLastWords', priority: -200)]
#[AsEventListener(event: DeathConfirmedPrePersistEvent::class, method: 'persistDeath', priority: -300)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'dispatchPictoUpdateEvent', priority: 0)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'dispatchSoulPointUpdateEvent', priority: -100)]
#[AsEventListener(event: DeathConfirmedPostPersistEvent::class, method: 'awardPrimeHxpForPictos', priority: -151)]
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
            UserUnlockableService::class,
            PictoService::class
        ];
    }

    public function awardGenerosity(DeathConfirmedEvent $event): void {

        if ($event->death->getGenerosityBonus() > 0 && !$event->death->getDisabled() && !$event->death->getTown()->getDisabled()) {

            $cache = $this->getService(DoctrineCacheService::class);

            $generosity = $cache->getEntityByIdentifier(FeatureUnlockPrototype::class, 'f_share');
            /** @var FeatureUnlock $instance */
            $instance = $this->getService(EntityManagerInterface::class)->getRepository(FeatureUnlock::class)->findBy([
                'user' => $event->user, 'expirationMode' => FeatureUnlock::FeatureExpirationTimestamp,
                'prototype' => $generosity
            ])[0] ?? null;

            if (!$instance) $instance = (new FeatureUnlock())->setPrototype( $generosity )->setUser( $event->user )
                ->setExpirationMode( FeatureUnlock::FeatureExpirationTimestamp )->setTimestamp( (new \DateTime('tomorrow'))->modify( "+{$event->death->getGenerosityBonus()}days" ) );
            else $instance->setTimestamp( (clone $instance->getTimestamp())->modify( "+{$event->death->getGenerosityBonus()}days" ) );

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

    private function hxp( CitizenRankingProxy $death, string|LogEntryTemplate $template, bool $global, int $value, array $props = [], ?string $subject = null ): void {
        if ($death->getTown()->getSeason())
            $this->getService(UserUnlockableService::class)
                ->recordHeroicExperience(
                    $death->getUser(),
                    $global ? HeroXPType::Global : HeroXPType::Seasonal,
                    $value,
                    $template,
                    $subject,
                    $props,
                    $death->getTown(),
                    $death,
                    $death->getTown()->getSeason()
                );
    }

    public function awardPrimeHxp(DeathConfirmedEvent $event): void {
        if ($event->death->getProperty( CitizenPersistentCache::ForceBaseHXP ) > 0) return;

        // 2xp for each citizen eaten as a ghoul
        if (($v = $event->death->getProperty( CitizenPersistentCache::Ghoul_Aggression )) > 0)
            $this->hxp( $event->death, 'hxp_ghoul_aggression', true, $v * 2, ['kills' => $v] );

        // 2xp for surviving hc town day 5
        if ($event->death->getTown()->getType()->is(TownClass::HARD) && $event->death->getDay() >= 5)
            $this->hxp( $event->death, 'hxp_panda_day5', true, 2, ['town' => $event->death->getTown()->getName()] );

        // 2xp for reaching D10 with each profession
        if ($event->death->getDay() >= 10) {
            $profession = $this->getService(EntityManagerInterface::class)->getRepository(CitizenProfession::class)->find( $event->death->getProperty( CitizenPersistentCache::Profession ) );
            if ($profession && $profession->getName() !== 'shaman')
                $this->hxp($event->death, 'hxp_profession_day10', false, 2, ['town' => $event->death->getTown()->getName(), 'profession' => $profession->getId()], "profession_day10_{$profession->getName()}");
        }

        // 5xp for surviving day 15
        if ($event->death->getDay() >= 15)
            $this->hxp( $event->death, 'hxp_common_day15', true, 5, ['town' => $event->death->getTown()->getName()] );

        // 10xp for surviving day 30 (one-time)
        if ($event->death->getDay() >= 30)
            $this->hxp( $event->death, 'hxp_common_day30', false, 10, ['town' => $event->death->getTown()->getName()], 'common_day30' );
    }

    public function awardPrimeHxpForPictos(DeathConfirmedEvent $event): void {
        if ($event->death->getProperty( CitizenPersistentCache::ForceBaseHXP ) > 0) return;

        $pt_2 = [ 1 => 2, 3 => 1, 5 => 1, 8 => 1, 10 => 1 ];
        $pt_5 = [ 1 => 5, 3 => 2, 5 => 2, 8 => 2, 10 => 2 ];
        $pt_7 = [ 1 => 7, 3 => 2, 5 => 2, 8 => 2, 10 => 2 ];

        $picto_db = [
            'r_surgrp_#00' => [ 'hxp_picto', false, 'by_day' => null, 'by_count' => [ 1 => 2 ] ],
            'r_surlst_#00' => [ 'hxp_picto', false, 'by_day' => [0 => 0, 5 => 7, 9 => 14, 14 => 21], 'by_count' => null ],
            'r_suhard_#00' => [ 'hxp_picto', false, 'by_day' => [0 => 0, 5 => 7], 'by_count' => null ],

            'r_thermal_#00' => [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_ebcstl_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_ebpmv_#00' =>   [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_ebgros_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_ebcrow_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_wondrs_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],
            'r_maso_#00'   =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_2 ],

            'r_batgun_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_5 ],
            'r_door_#00'   =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_5 ],
            'r_explo2_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_5 ],
            'r_ebuild_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_5 ],

            'r_chstxl_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_7 ],
            'r_dnucl_#00'  =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_7 ],
            'r_watgun_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_7 ],
            'r_cmplst_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => $pt_7 ],

            'r_tronco_#00' =>  [ 'hxp_picto_first', true, 'by_day' => null, 'by_count' => [ 1 => 10, 2 => 2, 3 => 2, 5 => 2 ] ],
        ];

        foreach ( $picto_db as $picto => [ 0 => $template, 1 => $subject, 'by_day' => $by_day, 'by_count' => $by_count ] ) {
            $value = 0;

            $count = $this->getService(EntityManagerInterface::class)->getRepository(Picto::class)->findOneBy([
                'user' => $event->user,
                'townEntry' => $event->death->getTown(),
                'prototype' => $prototype = $this
                    ->getService(DoctrineCacheService::class)
                    ->getEntityByIdentifier( PictoPrototype::class, $picto ),
                'persisted' => 2,
            ])?->getCount() ?? 0;

            if ($count <= 0) continue;

            if ( is_array($by_count) && $event->death->getTown()->getSeason() )
                $count = $this->getService(PictoService::class)
                    ->getSinglePictoCount( $event->user, $prototype, season: $event->death->getTown()->getSeason() );

            if (is_array($by_day)) {
                $set_v = 0;
                foreach ($by_day as $day => $v)
                    if ($event->death->getDay() >= $day)
                        $set_v = $v;
                $value += $set_v;
            }

            if ($count > 0) {

                if (is_array( $by_count ))
                    foreach ( $by_count as $min => $bonus )
                        if ($min <= $count && ($value + $bonus > 0))
                            $this->hxp($event->death, ($template . ( $min > 1 ? "_next" : "" )), !$subject, $value + $bonus,
                                       ['town' => $event->death->getTown()->getName(), 'picto' => $prototype->getId(), 'num' => $min],
                                       $subject ? ("picto_{$picto}" . ( $min > 1 ? "__$min" : "" )) : null);
                elseif ($value > 0)
                    $this->hxp($event->death, $template, !$subject, $value, ['town' => $event->death->getTown()->getName(), 'picto' => $prototype->getId()], $subject ? "picto_{$picto}" : null);
            }
        }


    }
}