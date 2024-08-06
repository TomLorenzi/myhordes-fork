<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\Citizen;
use App\Enum\ActionHandler\RelativeMaxPoint;
use App\Service\CitizenHandler;
use App\Service\DeathHandler;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Structures\ActionHandler\Execution;
use App\Structures\TownConf;
use App\Translation\T;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\StatusEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessStatusEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|StatusEffect $data): void
    {
        if (!$data->enableIf) return;

        /** @var CitizenHandler $ch */
        $ch = $this->container->get(CitizenHandler::class);

        /** @var RandomGenerator $rg */
        $rg = $this->container->get(RandomGenerator::class);

        /** @var Citizen $target */
        $target = $data->appliesToTarget ? $cache->target : $cache->citizen;

        if ($data->kill !== null) {
            /** @var DeathHandler $dh */
            $dh = $this->container->get(DeathHandler::class);

            /** @var LogTemplateHandler $log */
            $log = $this->container->get(LogTemplateHandler::class);

            $dh->kill( $target, $data->kill );
            $cache->em->persist( $log->citizenDeath( $target ) );

            return;
        }

        $p = $data->statusProbability;
        if ($p !== null && $data->statusProbabilityModifiable) {

            if ($data->role === 'ghoul') {
                if ($target->getTown()->getType()->getName() === 'panda') $p += 3;
                if ($ch->hasStatusEffect($target, 'tg_home_clean')) $p -= 3;
            }

        }

        if ($p === null || $rg->chance( $p / 100 )) {

            if ($data->resetThirstCounter)
                $target->setWalkingDistance(0);

            if ($data->actionCounterType !== null)
                $target->getSpecificActionCounter( $data->actionCounterType )->increment( $data->actionCounterValue );

            if ($data->ghoulHunger) {
                $ghoul_mode = $cache->conf->get(TownConf::CONF_FEATURE_GHOUL_MODE, 'normal');
                $hungry_ghouls = $cache->conf->get(TownConf::CONF_FEATURE_GHOULS_HUNGRY, false);
                if (($hungry_ghouls || $target->hasRole('ghoul')) && ($data->ghoulHungerForced || !in_array($ghoul_mode, ['bloodthirst','airbnb'])))
                    $target->setGhulHunger( max(0,$target->getGhulHunger() + $data->ghoulHunger) );
            }

            if ($data->role) {
                if ($data->roleIsAdded) {
                    if ($ch->addRole( $target, $data->role )) {
                        $cache->addTag('role-up');
                        $cache->addTag("role-up-{$data->role}");
                    }
                } else {
                    if ($ch->removeRole( $target, $data->role )) {
                        $cache->addTag('role-down');
                        $cache->addTag("role-down-{$data->role}");
                    }
                }
            }

            if ($data->statusFrom && $data->statusTo) {

                if ($target->hasStatus( $data->statusFrom )) {
                    $ch->removeStatus( $target, $data->statusFrom );
                    $ch->inflictStatus( $target, $data->statusTo );
                    $cache->addTag('stat-change');
                    $cache->addTag("stat-change-{$data->statusFrom}-{$data->statusTo}");
                }
            }
            elseif ($data->statusFrom) {
                if ($target->hasStatus( $data->statusFrom ) && $ch->removeStatus( $target, $data->statusFrom )) {
                    $cache->addTag('stat-down');
                    $cache->addTag("stat-down-{$data->statusFrom}");
                }
            }
            elseif ($data->statusTo) {
                $inflict = true;

                if ($data->statusTo === "infect" && $ch->hasStatusEffect($target, "tg_infect_wtns")) {
                    $inflict = $rg->chance(0.5);
                    $ch->removeStatus( $target, 'tg_infect_wtns' );

                    $cache->addMessage(
                                           $inflict
                                               ? T::__('Ein Opfer der Großen Seuche zu sein hat dir diesmal nicht viel gebracht... und es sieht nicht gut aus...', "items")
                                               : T::__('Da hast du wohl Glück gehabt... Als Opfer der Großen Seuche bist du diesmal um eine unangenehme Infektion herumgekommen.', "items"),
                        translationDomain: 'items'
                    );
                }
                if ($inflict) {
                    if (!$target->hasStatus( $data->statusTo ) && $ch->inflictStatus($target, $data->statusTo)) {
                        $cache->addTag('stat-up');
                        $cache->addTag("stat-up-{$data->statusTo}");
                    }
                }
            }
        }

        if ($data->pointType !== null) {
            $old_pt = $target->getPoints( $data->pointType );
            if ($data->pointRelativeToMax?->isRelative()) {
                $base = $ch->getMaxPoints($target, $data->pointType, $data->pointRelativeToMax !== RelativeMaxPoint::RelativeToExtensionMax );
                $to = min(($base > 0) ? ($base + $data->pointValue) : 0, $data->pointCapAt ?? PHP_INT_MAX);
                $ch->setPoints( $target, $data->pointType, false, max( $old_pt, $to ), null );
            } else {
                $base = $target->getPoints( $data->pointType );
                $to = min($base + $data->pointValue, $data->pointCapAt ?? PHP_INT_MAX);
                $ch->setPoints($target, $data->pointType, false, $to, $data->pointValue < 0 ? null : $data->pointExceedMax);
            }

            $cache->addPoints( $data->pointType, $target->getPoints( $data->pointType ) - $old_pt );
        }
    }
}