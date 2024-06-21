<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\ChatSilenceTimer;
use App\Entity\EscapeTimer;
use App\Entity\LogEntryTemplate;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Enum\ActionHandler\CountType;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\LogTemplateHandler;
use App\Service\Maps\MazeMaker;
use App\Service\PictoHandler;
use App\Service\TownHandler;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\ZoneEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessZoneEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|ZoneEffect $data): void
    {
        $base_zone = $cache->citizen->getZone();

        /** @var TownHandler $th */
        $th = $this->container->get(TownHandler::class);
        /** @var LogTemplateHandler $lh */
        $lh = $this->container->get(LogTemplateHandler::class);

        if ($data->uncover > 0) {
            $base_zone_x = $base_zone?->getX() ?? 0;
            $base_zone_y = $base_zone?->getY() ?? 0;

            $upgraded_map = $th->getBuilding($cache->citizen->getTown(),'item_electro_#00', true);
            for ($x = -$data->uncover; $x <= $data->uncover; $x++)
                for ($y = -$data->uncover; $y <= $data->uncover; $y++) {
                    /** @var Zone $zone */
                    $zone = $cache->em->getRepository(Zone::class)->findOneByPosition($cache->citizen->getTown(), $base_zone_x + $x, $base_zone_y + $y);
                    if ($zone) {
                        $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                        if ($upgraded_map) $zone->setZombieStatus( Zone::ZombieStateExact );
                        else $zone->setZombieStatus( max( $zone->getZombieStatus(), Zone::ZombieStateEstimate ) );
                    }
                }
        }

        if ($base_zone) {

            if ($data->hasCleanupEffect()) {
                $count = min(mt_rand($data->cleanMin,$data->cleanMax), $base_zone->getBuryCount());
                $cache->addToCounter( CountType::Bury, $count );

                $base_zone->setBuryCount( max(0, $base_zone->getBuryCount() - $count ));
                if ($base_zone->getPrototype())
                    $cache->em->persist( $lh->outsideUncover( $cache->citizen, $count, $cache->originalPrototype ) );
                if ($base_zone->getBuryCount() == 0)
                    $cache->em->persist( $lh->outsideUncoverComplete( $cache->citizen ) );
            }

            if ($data->hasKillEffect()) {
                /** @var PictoHandler $ph */
                $ph = $this->container->get(PictoHandler::class);
                if ($cache->getTargetRuinZone()) {
                    $kills = min($cache->getTargetRuinZone()->getZombies(), mt_rand( $data->zombieMin, $data->zombieMax));
                    if ($kills > 0) {
                        $cache->getTargetRuinZone()->setZombies( $cache->getTargetRuinZone()->getZombies() - $kills );
                        $cache->getTargetRuinZone()->setKilledZombies( $cache->getTargetRuinZone()->getKilledZombies() + $kills );
                        $cache->addToCounter( CountType::Kills, $kills );
                        $ph->give_picto($cache->citizen, 'r_killz_#00', $kills);
                        $cache->em->persist( $lh->zombieKill( $cache->citizen, $cache->originalPrototype, $kills, $cache->getAction()?->getName() ) );
                        if($cache->getTargetRuinZone()->getZombies() <= 0)
                            $cache->addTag('kill-latest');
                    }
                }
                else {
                    $kills = min($cache->citizen->getZone()->getZombies(), mt_rand($data->zombieMin, $data->zombieMax));
                    if ($kills > 0) {
                        $cache->citizen->getZone()->setZombies( $cache->citizen->getZone()->getZombies() - $kills );
                        $cache->addToCounter( CountType::Kills, $kills );
                        if (!$cache->isFlagged('kills_silent'))
                            $cache->em->persist( $lh->zombieKill( $cache->citizen, $cache->originalPrototype, $kills, $cache->getAction()?->getName() ) );
                        $ph->give_picto($cache->citizen, 'r_killz_#00', $kills);
                        if($cache->citizen->getZone()->getZombies() <= 0)
                            $cache->addTag('kill-latest');
                    }
                }
            }

            if ($data->escape > 0) {
                $cache->addTag('any-escape');

                if ($cache->getTargetRuinZone()) {
                    $z = $cache->getTargetRuinZone()->getZombies();
                    $cache->getTargetRuinZone()->setZombies( 0 );
                    if ($z > 0) {
                        /** @var MazeMaker $mm */
                        $mm = $this->container->get(MazeMaker::class);
                        $mm->populateMaze($cache->getTargetRuinZone()->getZone(), $z, false, false, [$cache->getTargetRuinZone()]);
                    }
                    $cache->addToCounter( CountType::Zombies, $z );
                    $cache->addTag('reverse-escape');
                } else {
                    $base_zone->addEscapeTimer((new EscapeTimer())->setTime(new \DateTime("+{$data->escape}sec")));
                    switch ($data->escapeTag) {
                        case 'armag':
                            $cache->em->persist( $lh->zoneEscapeArmagUsed( $cache->citizen, $data->escape, 1 ) );
                            $cache->addFlag('kills_silent');
                            break;
                        default:
                            if ($cache->originalPrototype)
                                $cache->em->persist( $lh->zoneEscapeItemUsed( $cache->citizen, $cache->originalPrototype, $data->escape ) );
                            break;
                    }

                    $cache->addTag('escape');
                }
            }

            if ($data->improveLevel <> 0.0)
                $base_zone->setImprovementLevel( $base_zone->getImprovementLevel() + $data->improveLevel );

            if ($data->chatSilence > 0) {
                $base_zone->addChatSilenceTimer((new ChatSilenceTimer())->setTime(new \DateTime("+{$data->chatSilence}sec"))->setCitizen($cache->citizen));
                $limit = new \DateTime("-3min");

                foreach ($cache->em->getRepository(TownLogEntry::class)->findByFilter( $base_zone->getTown(), null, null, $base_zone ) as $entry) {
                    /** @var TownLogEntry $entry */
                    if ($entry->getLogEntryTemplate() !== null) {
                        $suffix = '';
                        switch ($entry->getLogEntryTemplate()->getClass()) {
                            case LogEntryTemplate::ClassWarning:
                                $suffix = "Warning";
                                break;
                            case LogEntryTemplate::ClassCritical:
                                $suffix = "Critical";
                                break;
                            case LogEntryTemplate::ClassChat:
                                $suffix = "Chat";
                                break;
                            case LogEntryTemplate::ClassDanger:
                                $suffix = "Danger";
                                break;
                        }

                        $template = $cache->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'smokeBombReplacement' . $suffix]);
                        if ($template && $entry->getTimestamp() > $limit) {
                            ($this->container->get(InvalidateLogCacheAction::class))($entry);
                            $entry->setLogEntryTemplate($template);
                            $cache->em->persist($entry);
                        } else break;
                    }
                }
                $cache->em->persist($lh->smokeBombUsage($base_zone));
            }
        }
    }
}