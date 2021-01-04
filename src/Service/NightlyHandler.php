<?php
namespace App\Service;

use App\Entity\Building;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\CitizenVote;
use App\Entity\CitizenWatch;
use App\Entity\DigRuinMarker;
use App\Entity\EscapeTimer;
use App\Entity\Gazette;
use App\Entity\GazetteLogEntry;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Structures\EventConf;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NightlyHandler
{
    private array $cleanup = [];
    private array $skip_reanimation = [];
    private array $skip_infection = [];

    private EntityManagerInterface $entity_manager;
    private LoggerInterface $log;
    private CitizenHandler $citizen_handler;
    private RandomGenerator $random;
    private DeathHandler $death_handler;
    private TownHandler $town_handler;
    private ZoneHandler $zone_handler;
    private InventoryHandler $inventory_handler;
    private PictoHandler $picto_handler;
    private ItemFactory $item_factory;
    private LogTemplateHandler $logTemplates;
    private ConfMaster $conf;
    private ActionHandler $action_handler;
    private MazeMaker $maze;
    private CrowService $crow;

    public function __construct(EntityManagerInterface $em, LoggerInterface $log, CitizenHandler $ch, InventoryHandler $ih,
                              RandomGenerator $rg, DeathHandler $dh, TownHandler $th, ZoneHandler $zh, PictoHandler $ph,
                              ItemFactory $if, LogTemplateHandler $lh, ConfMaster $conf, ActionHandler $ah, MazeMaker $maze,
                              CrowService $crow)
    {
        $this->entity_manager = $em;
        $this->citizen_handler = $ch;
        $this->death_handler = $dh;
        $this->inventory_handler = $ih;
        $this->random = $rg;
        $this->town_handler = $th;
        $this->zone_handler = $zh;
        $this->picto_handler = $ph;
        $this->item_factory = $if;
        $this->log = $log;
        $this->logTemplates = $lh;
        $this->conf = $conf;
        $this->action_handler = $ah;
        $this->maze = $maze;
        $this->crow = $crow;
    }

    private function check_town(Town $town): bool {
        if ($town->isOpen()) {
            $this->log->debug('The town lobby is <comment>open</comment>!');
            $this->entity_manager->persist($this->logTemplates->nightlyAttackCancelled($town));
            return false;
        }

        $alive = false;
        foreach ($town->getCitizens() as $citizen)
            if ($citizen->getAlive()) {
                $alive = true;
                break;
            }

        if (!$alive) {
            $this->log->debug('The town has <comment>no</comment> living citizen!');
            return false;
        }

        $this->town_handler->check_gazettes($town);

        return true;
    }

    private function kill_wrap( Citizen &$citizen, CauseOfDeath $cod, bool $skip_reanimation = false, int $zombies = 0, $skip_log = false, ?int $day = null ) {
        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> dies of <info>{$cod->getLabel()}</info>.");
        $this->death_handler->kill($citizen,$cod,$rr);

        if (!$skip_log) $this->entity_manager->persist( $this->logTemplates->citizenDeath( $citizen, $zombies, null, $day ) );
        foreach ($rr as $r) $this->cleanup[] = $r;
        if ($skip_reanimation) $this->skip_reanimation[] = $citizen->getId();
    }

    private function stage1_prepare(Town $town) {
        /*$this->log->info('<info>Checking insurrection status</info> ...');

        if ($town->getInsurrectionProgress() > 0)
            foreach ($town->getCitizens() as $citizen)
                if ($citizen->getAlive() && $citizen->getBanished() && !$this->citizen_handler->hasStatusEffect($citizen, 'tg_insurrection')) {
                    $this->log->info("Shunned citizen <info>{$citizen->getUser()->getName()}</info> did not contribute to the insurrection. Resetting.");
                    $town->setInsurrectionProgress(0);
                    break;
                }
        */
    }

    private function stage1_vanish(Town $town) {
        $this->log->info('<info>Vanishing citizens</info> ...');
        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Vanished]);

        $camp_1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_hide']);
        $camp_2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_tomb']);

        foreach ($town->getCitizens() as $citizen)
            if ($citizen->getAlive() && $citizen->getZone()) {

                $citizen_hidden = $citizen->getStatus()->contains( $camp_1 ) || $citizen->getStatus()->contains( $camp_2 );
                if ($citizen_hidden) {
                    // This poor soul wants to camp outside.
                    $survival_chance = $citizen->getCampingChance();

                    if (!$this->random->chance($survival_chance)) {
                        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> was at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> and died while camping (survival chance was " . ($survival_chance * 100) . "%)!");
                        $this->kill_wrap($citizen, $cod, false, 0, false, $town->getDay()+1);
                    }
                    else {
                        $citizen->setCampingCounter($citizen->getCampingCounter() + 1);
                        // Grant blueprint if first camping on a ruin.
                        if ($citizen->getZone()->getBlueprint() === Zone::BlueprintAvailable && $citizen->getZone()->getBuryCount() <= 0) {
                            // Spawn BP.
                            $bp_name = ($this->zone_handler->getZoneKm($citizen->getZone()) < 10)
                                ? 'bplan_u_#00'
                                : 'bplan_r_#00';
                            $bp_item_prototype = $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $bp_name]);
                            $bp_item = $this->item_factory->createItem( $bp_item_prototype );
                            $citizen->getZone()->getFloor()->addItem($bp_item);
                            // Set zone blueprint.
                            $citizen->getZone()->setBlueprint(Zone::BlueprintFound);
                        }
                        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> survived camping at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> with a survival chance of <info>" . ($survival_chance * 100) . "%</info>.");
                    }
                }
                else {
                  $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> without protection!");
                  $this->kill_wrap($citizen, $cod, false, 0, false, $town->getDay()+1);
                }
            }
    }

    private function stage1_status(Town $town) {
        $this->log->info('<info>Processing status-related deaths</info> ...');
        $cod_thirst = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Dehydration]);
        $cod_addict = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Addiction]);
        $cod_infect = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Infection]);
        $cod_ghoul  = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::GhulStarved]);

        $status_infected  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'infection']);
        $status_survive   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'hsurvive']);
        $status_thirst2   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'thirst2']);
        $status_drugged   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'drugged']);
        $status_addicted  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'addict']);

        foreach ($town->getCitizens() as $citizen) {

            if (!$citizen->getAlive()) continue;

            $ghoul = $citizen->hasRole('ghoul');

            if ($citizen->getStatus()->contains( $status_survive )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> is <info>protected</info> by <info>{$status_survive->getLabel()}</info>." );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_thirst2 ) && !$ghoul) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_thirst2->getLabel()}</info>." );
                $this->kill_wrap( $citizen, $cod_thirst, true, 0, false, $town->getDay()+1 );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_addicted ) && !$citizen->getStatus()->contains( $status_drugged )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_addicted->getLabel()}</info>, but not <info>{$status_drugged->getLabel()}</info>." );
                $this->kill_wrap( $citizen, $cod_addict, true, 0, false, $town->getDay()+1 );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_infected ) && !$ghoul) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_infected->getLabel()}</info>." );
                if ($this->random->chance($this->conf->getTownConfiguration($town)->get( TownConf::CONF_MODIFIER_INFECT_DEATH, 0.5 ))) $this->kill_wrap( $citizen, $cod_infect, true, 0, false, $town->getDay()+1 );
                continue;
            }

            if ($ghoul && $citizen->getGhulHunger() > 40) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> is a <info>hungry ghoul</info>." );
                $this->kill_wrap( $citizen, $cod_ghoul, true, 0, false, $town->getDay()+1 );
                continue;
            }
        }
    }

    private function stage2_day(Town $town) {
        $this->log->info('<info>Updating survival information</info> ...');
        foreach ($town->getCitizens() as $citizen) {
            if (!$citizen->getAlive()) continue;

            if (!$citizen->getProfession()->getHeroic())
                continue;

            // Check hero skills
            $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($citizen->getUser()->getAllHeroDaysSpent());
            
            $citizen->getUser()->setHeroDaysSpent($citizen->getUser()->getHeroDaysSpent() + 1);

            if($nextSkill !== null && $citizen->getUser()->getAllHeroDaysSpent() >= $nextSkill->getDaysNeeded()){
                $this->log->info("Citizen <info>{$citizen->getUser()->getUsername()}</info> has unlocked a new skill : <info>{$nextSkill->getTitle()}</info>");

                switch($nextSkill->getName()){
                    case "brothers":
                        //TODO: add the heroic power
                        break;
                    case "largechest1":
                    case "largechest2":
                        $citizen->getHome()->setAdditionalStorage($citizen->getHome()->getAdditionalStorage() + 1);
                        break;
                    case "secondwind":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_ap"]);
                        $citizen->addHeroicAction($heroic_action);
                        break;
                    case "cheatdeath":
                        $heroic_action = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_immune"]);
                        $citizen->addHeroicAction($heroic_action);
                        break;
                    case 'luckyfind':
                        $oldfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_find"]);
                        if($citizen->getHeroicActions()->contains($oldfind)) {
                            // He didn't used the Find, we replace it with the lucky find
                            $citizen->removeHeroicAction($oldfind);
                            $newfind = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => "hero_generic_find_lucky"]);
                            $citizen->addHeroicAction($newfind);
                        }
                }
                $this->entity_manager->persist($citizen);
                $this->entity_manager->persist($citizen->getHome());
            }
        }
    }

    private function stage2_pre_attack_buildings(Town &$town){
        $this->log->info('Inflicting damages to buildings before the attack');

        $reactor = $this->town_handler->getBuilding($town, 'small_arma_#00', true);
        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::Radiations]);

        if ($reactor) {
            $damages = mt_rand(50, 125);
            $reactor->setHp(max(0, $reactor->getHp() - $damages));

            $newDef = round(max(0, $reactor->getPrototype()->getDefense() * $reactor->getHp() / $reactor->getPrototype()->getHp()));

            $this->log->debug("The <info>reactor</info> has taken <info>$damages</info> damages. It now has $newDef defense...");

            $reactor->setDefense($newDef);
            if($reactor->getHp() <= 0){
                $gazette = $town->findGazette( $town->getDay() );

                $this->entity_manager->persist($this->logTemplates->constructionsDestroy($town, $reactor->getPrototype(), $damages ));
                $this->town_handler->destroy_building($town, $reactor);

                $this->log->debug("The reactor is destroyed. Everybody dies !");

                // It is destroyed, let's kill everyone with the good cause of death
                $citizens = $this->town_handler->get_alive_citizens($town);

                foreach ($citizens as $citizen) {
                    $gazette->setDeaths($gazette->getDeaths() + 1);
                    $this->kill_wrap($citizen, $cod, true, 0, false, $town->getDay());
                }

                $this->entity_manager->persist($gazette);
            } else {
                $this->entity_manager->persist($this->logTemplates->constructionsDamage($town, $reactor->getPrototype(), $damages ));
            }
        }
    }

    private function stage2_surprise_attack(Town $town) {
        $this->log->info('<info>Awakening the dead</info> ...');
        /** @var Citizen[] $houses */
        $houses = [];
        /** @var Citizen[] $targets */
        $targets = [];
        /** @var Building[] $buildings */
        $buildings = [];

        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::NightlyAttack]);

        foreach ($town->getCitizens() as $citizen) {
            /** @var Citizen $citizen */
            if ($citizen->getAlive() && !$citizen->getZone() && $citizen->getUser()->getName() !== "user_001")
                $targets[] = $citizen;
            elseif (!$citizen->getAlive() && $citizen->getHome()->getHoldsBody() && !in_array($citizen->getId(), $this->skip_reanimation))
                $houses[] = $citizen;
        }
        foreach ($town->getBuildings() as $building)
            if ($building->getAp() > 0 && !$building->getComplete())
                $buildings[] = $building;

        $this->log->debug( '<info>' . count($houses) . '</info> corpses have been reanimated!' );
        $targets = $this->random->pick($targets, min(count($houses),count($targets)), true);
        $buildings = $this->random->pick($buildings, min(count($houses),count($buildings)), true);

        if(count($houses) > 0){
            $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackStart($town) );
        }

        $gazette = $town->findGazette( $town->getDay() );
        $useless = 0;

        foreach ($houses as $id => $corpse) {
            /** @var Citizen $corpse */

            $opts = [];
            $opts[] = 0;
            if (!empty( $targets )) $opts[] = 1;
            if (!empty( $buildings )) $opts[] = 2;
            if ($town->getWell() > 0) $opts[] = 3;


            /*if (empty($opts)) {
                $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> has nothing to do.");
                $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackNothing( $corpse ) );
                continue;
            }*/

            switch ($this->random->pick($opts, 1)) {
                case 0:
                    $useless++;
                    $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> has nothing to do.");
                    $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackNothing( $corpse ) );
                    break;
                case 1:
                    $victim = array_pop($targets);
                    $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> attacks and kills <info>{$victim->getUser()->getUsername()}</info>.");
                    $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackKill( $corpse, $victim ) );
                    $corpse->setHasEaten(true);
                    $this->entity_manager->persist($corpse);
                    $this->kill_wrap( $victim, $cod, false, 1, true );
                    break;
                case 2:
                    $construction = array_pop($buildings);
                    $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> destroys the progress on <info>{$construction->getPrototype()->getLabel()}</info>.");
                    $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackDestroy( $corpse, $construction ) );
                    $construction->setAp(0);
                    break;
                case 3:
                    $d = min($town->getWell(), 20);
                    $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> removes <info>{$d} water rations</info> from the well.");
                    // Disabled because in Hordes, this is not shown as lost
                    // $gazette->setWaterlost($gazette->getWaterlost() + $d);
                    if($d > 0){
                        $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackWell( $corpse, $d ) );
                        $town->setWell( $town->getWell() - $d );
                    }
                    break;
            }
        }
        if($useless > 0){
            $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackNothingSummary( $town, $useless ) );
        }

        $this->entity_manager->persist($gazette);
    }

    private function stage2_attack(Town &$town) {
        $this->log->info('<info>Marching the horde</info> ...');
        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => CauseOfDeath::NightlyAttack]);
        $status_terror  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'terror']);

        $has_kino = $this->town_handler->getBuilding($town, 'small_cinema_#00', true);

        // Day already advanced, let's get today's gazette!
        /** @var Gazette $gazette */
        $gazette = $town->findGazette( $town->getDay() );

        $gazette->setDoor($town->getDoor());

	    $def  = $this->town_handler->calculate_town_def( $town );
	    if($town->getDevastated())
	        $def = 0;

	    $gazette->setDefense($def);

        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()-1);
        $zombies = $est ? $est->getZombies() : 0;

        $soulFactor = min(1 + (0.04 * $this->town_handler->get_red_soul_count($town)), (float)$this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_RED_SOUL_FACTOR, 1.2));

        $zombies *= $soulFactor;
        $zombies = round($zombies);

        $gazette->setAttack($zombies);

        $overflow = !$town->getDoor() ? max(0, $zombies - $def) : $zombies;
        $this->log->debug("The town has <info>{$def}</info> defense and is attacked by <info>{$zombies}</info> Zombies. The door is <info>" . ($town->getDoor() ? 'open' : 'closed') . "</info>!");
        $this->log->debug("<info>{$overflow}</info> Zombies have entered the town!");

        $gazette->setInvasion($overflow);

        $this->entity_manager->persist( $this->logTemplates->nightlyAttackBegin($town, $zombies) );
        
        $this->log->debug("Getting watchers for day " . $town->getDay());

        /** @var CitizenWatch[] $watchers */
        $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchersOfDay($town, $town->getDay() - 1); // -1 because day has been advanced before stage2

        if(count($watchers) > 0)
            $this->entity_manager->persist($this->logTemplates->nightlyAttackWatchers($town, $watchers));

        $this->entity_manager->persist( $this->logTemplates->nightlyAttackSummary($town, $town->getDoor(), $overflow) );

        $total_watch_def = $this->town_handler->calculate_watch_def($town);
        $zeds_each_watcher = -1;
        if($total_watch_def > $overflow){
            // If we have more watchpoint than the overflow,
            // we dispatch the zeds
            $zeds_each_watcher = $overflow / count($town->getCitizenWatches());
        }

        $this->log->debug("There are <info>".count($watchers)."</info> watchers in the town, against <info>$overflow</info> zombies");

        $defWatchers = 0;

        $has_shooting_gallery = (bool)$this->town_handler->getBuilding($town, 'small_tourello_#00', true);
        $has_trebuchet        = (bool)$this->town_handler->getBuilding($town, 'small_catapult3_#00', true);
        $has_ikea             = (bool)$this->town_handler->getBuilding($town, 'small_ikea_#00', true);
        $has_armory           = (bool)$this->town_handler->getBuilding($town, 'small_armor_#00', true);

        foreach ($watchers as $watcher) {
            $defBonus = $zeds_each_watcher == -1 ? $this->citizen_handler->getNightWatchDefense($watcher->getCitizen(), $has_shooting_gallery, $has_trebuchet, $has_ikea, $has_armory) : $zeds_each_watcher;

            $defWatchers += $defBonus;

            $deathChances = $this->citizen_handler->getDeathChances($watcher->getCitizen(), true);
            $woundOrTerrorChances = $deathChances + $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WOUND_TERROR_PENALTY, 0.05);
            $ctz = $watcher->getCitizen();
            if($this->random->chance($deathChances)){
                $this->log->debug("Watcher <info>{$watcher->getCitizen()->getUser()->getUsername()}</info> is now <info>dead</info> because of the watch");
                $skip = false;
                // too sad, he died by falling from the edge
                if($overflow <= 0) {
                    $this->entity_manager->persist($this->logTemplates->citizenDeathOnWatch($watcher->getCitizen(), 0));
                    $skip = true;
                }
                $this->kill_wrap($ctz, $cod, false, ($zeds_each_watcher > 0 ? $zeds_each_watcher : 0), $skip);
            } else if($overflow > 0 && $this->random->chance($woundOrTerrorChances)){
                if($this->random->chance(0.5)){
                    // Wound
                    if (!$this->citizen_handler->isWounded($ctz)) {
                        $this->citizen_handler->inflictWound($ctz);
                        $this->log->debug("Watcher <info>{$ctz->getUser()->getUsername()}</info> is now <info>wounded</info>");
                        $this->skip_infection[] = $ctz->getId();
                    }
                } elseif(!$this->town_handler->getBuilding($town, "small_catapult3_#00", true)) {
                    // Terror
                    $this->citizen_handler->inflictStatus($ctz, $status_terror);
                    $this->log->debug("Watcher <info>{$ctz->getUser()->getUsername()}</info> now suffers from <info>{$status_terror->getLabel()}</info>");

                    $gazette->setTerror($gazette->getTerror() + 1);
                }
            }

            if($overflow > 0){
                $this->log->debug("Watcher <info>{$watcher->getCitizen()->getUser()->getUsername()}</info> has stopped <info>$defBonus</info> zombies from his watch");

                $null = null;
                foreach ($watcher->getCitizen()->getInventory()->getItems() as $item)
                    if ($item->getPrototype()->getNightWatchAction()) {
                        $this->log->debug("Executing night watch action for '<info>{$item->getPrototype()->getLabel()}</info>' held by Watcher <info>{$watcher->getCitizen()->getUser()->getUsername()}</info>.");
                        $this->action_handler->execute( $ctz, $item, $null, $item->getPrototype()->getNightWatchAction(), $msg, $r, true);
                        foreach ($r as $rr) $this->entity_manager->remove($rr);
                    }

                $overflow -= $defBonus;
            } else {
                $watcher->setSkipped(true);
                $this->entity_manager->persist($watcher);
            }
        }


        if ($this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_BUILDING_DAMAGE)) {
            // In panda, built buildings get damaged every night
            $damageInflicted = $zombies;
            //if($overflow > 0)
            //    $damageInflicted -= $defWatchers;

            // Only 10% of the attack is inflicted to buildings
            $damageInflicted = round($damageInflicted * 0.1, 0);

            $this->log->debug("Inflicting <info>$damageInflicted</info> damage to the buildings in town...");

            $targets = [];

            foreach ($town->getBuildings() as $building) {
                // Only built buildings AND buildings with HP can get damaged
                if (!$building->getComplete() || $building->getPrototype()->getHp() == 0 || $building->getPrototype()->getImpervious()) continue;
                $targets[] = $building;
            }

            shuffle($targets);

            while ($damageInflicted > 0 && !empty($targets)) {
                $target = array_pop($targets);

                $damages = min($damageInflicted, min($target->getHp(), mt_rand(0, ceil($target->getPrototype()->getHp() * 0.7))));

                if ($damages <= 0) continue;

                $this->log->debug("The <info>{$target->getPrototype()->getLabel()}</info> has taken <info>$damages</info> damages.");
                $target->setHp(max(0, $target->getHp() - $damages));

                if($target->getPrototype()->getDefense() > 0){
                    $newDef = round(max(0, $target->getPrototype()->getDefense() * $target->getHp() / $target->getPrototype()->getHp()));
                    $this->log->debug("It now has <info>$newDef</info> defense...");
                    $target->setDefense($newDef);
                }

                if($target->getHp() <= 0){
                    $this->entity_manager->persist($this->logTemplates->constructionsDestroy($town, $target->getPrototype(), $damages ));
                    $this->town_handler->destroy_building($town, $target);
                } else {
                    $this->entity_manager->persist($this->logTemplates->constructionsDamage($town, $target->getPrototype(), $damages ));
                }

                $damageInflicted -= $damages;
            }
        }

        if ($overflow <= 0) {
            $this->entity_manager->persist($gazette);
            return;
        }
		
        $survival_count = 0;
        /** @var Citizen[] $targets */
        $targets = [];
        foreach ($town->getCitizens() as $citizen) {
            if ($citizen->getAlive()) {
                $survival_count++;
                if (!$citizen->getZone())
                    $targets[] = $citizen;
            }
        }
					
		
        shuffle($targets);
		
        $attack_day = $town->getDay();
		if ($attack_day <= 3) $max_active = round($zombies*0.5*mt_rand(90,140)/100); 
		elseif ($attack_day <= 14) $max_active = $attack_day * 15;
		elseif ($attack_day <= 18) $max_active = ($attack_day + 4)*15;
		elseif ($attack_day <= 23) $max_active = ($attack_day + 5)*15;
		else                       $max_active = ($attack_day + 6)*15;
		
		
		//$in_town = $town->getChaos() ? max(10,count($targets)) : count($targets);
		$in_town = min(8, ceil(count($targets) * 0.85));
		
		$attacking = min($max_active, $overflow);

		$targets = $this->random->pick($targets, $in_town, true);

        $this->log->debug("<info>{$attacking}</info> Zombies are attacking <info>" . count($targets) . "</info> citizens!");
        $this->entity_manager->persist( $this->logTemplates->nightlyAttackLazy($town, $attacking) );
		

		$repartition = array_fill(0, count($targets), 0);
		for ($i = 0; $i < count($repartition); $i++) {
			$repartition[$i] =  mt_rand() / mt_getrandmax(); //random value between 0 and 1.0 with many decimals
		}
		
		if(count($repartition) != 0) {
			//one citizen gets especially unlucky
			$repartition[mt_rand(0, count($repartition)-1)] += 0.3;
		}
		
		$sum = array_sum($repartition);
		
		for ($i = 0; $i < count($repartition); $i++) {
			$repartition[$i] /= $sum;
			$repartition[$i] = round($repartition[$i]*$attacking);
		}
		
		
		//remove citizen receiving 0 zombie
		foreach (array_keys($repartition, 0) as $key) {
			unset($repartition[$key]);
		}
		
		rsort($repartition);
				
        for ($i = 0; $i < count($repartition); $i++) {
            $home = $targets[$i]->getHome();
			
			$force = $repartition[$i];
			
            $def = $this->town_handler->calculate_home_def($home);
            $this->log->debug("Citizen <info>{$targets[$i]->getUser()->getUsername()}</info> is attacked by <info>{$force}</info> zombies and protected by <info>{$def}</info> home defense!");
		
            if ($force > $def){
                $this->kill_wrap($targets[$i], $cod, false, $force);
				
                // citizen dies from the attack, citizen validate the new day
                $gazette->setDeaths($gazette->getDeaths() + 1);
            }
            else {
                $this->entity_manager->persist($this->logTemplates->citizenZombieAttackRepelled( $targets[$i], $def, $force));
                // Calculate decoration
                $deco = 0;
                foreach ($targets[$i]->getHome()->getChest()->getItems() as $item)
                    $deco += $item->getPrototype()->getDeco();

                if (!$has_kino && !$this->citizen_handler->hasStatusEffect($targets[$i], $status_terror)) {
                    if ($this->random->chance(1 - ($deco / 100))) {
                        $this->citizen_handler->inflictStatus( $targets[$i], $status_terror );
                        $this->log->debug("Citizen <info>{$targets[$i]->getUser()->getUsername()}</info> now suffers from <info>{$status_terror->getLabel()}</info>");

                        $this->crow->postAsPM($targets[$i], '', '', PrivateMessage::TEMPLATE_CROW_TERROR, $force);

                        $gazette->setTerror($gazette->getTerror() + 1);
                    } else {
                        $this->crow->postAsPM($targets[$i], '', '', PrivateMessage::TEMPLATE_CROW_AVOID_TERROR, $force);
                    }
                }
            }
        }

        $this->entity_manager->persist($gazette);
    }

    private function stage2_post_attack_buildings(Town &$town){
        $this->log->info('Inflicting damages to buildings after the attack');
        $fireworks = $this->town_handler->getBuilding($town, 'small_fireworks_#00', true);
        if($fireworks){
            $fireworks->setHp(max(0, $fireworks->getHp() - 20));

            $this->log->debug("The <info>fireworks</info> has taken <info>20</info> damages...");
            $newDef = round(max(0, $fireworks->getPrototype()->getDefense() * $fireworks->getHp() / $fireworks->getPrototype()->getHp()));

            $this->log->debug("It now has $newDef defense...");

            $fireworks->setDefense($newDef);
            if($fireworks->getHp() <= 0) {
                // It is destroyed, let's do this !
                $this->entity_manager->persist($this->logTemplates->fireworkExplosion($town, $fireworks->getPrototype()));

                $this->town_handler->destroy_building($town, $fireworks);

                $this->log->debug("The fireworks are destroyed. Half of citizens in town gets infected !");

                // Fetching alive citizens
                $citizens = $this->town_handler->get_alive_citizens($town);
                $toInfect = [];
                // Keeping citizens in town
                foreach ($citizens as $citizen) {
                    /** @var Citizen $citizen */
                    if($citizen->getZone() || !$citizen->getAlive()) continue;
                    $toInfect[] = $citizen;
                }

                // Randomness
                shuffle($toInfect);
                // We infect the first half of the list
                for ($i=0; $i < count($toInfect) / 2; $i++) { 
                    $this->citizen_handler->inflictStatus($toInfect[$i], "infection");
                }

                // Kill zombies around the town (all at 1km, none beyond 10km)
                foreach ($town->getZones() as $zone) {
                	$km = $this->zone_handler->getZoneKm($zone);
                	if($km > 10) continue;

                    $factor = 1 - max(0, (11 - $km) / 10);
                    $zone->setZombies($zone->getZombies() * $factor);
                }
                
                //TODO: Lower the attack for 2-3 days

            } else {
                $this->entity_manager->persist($this->logTemplates->constructionsDamage($town, $fireworks->getPrototype(), 20 ));
            }
            $this->entity_manager->persist($fireworks);
        }

        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {
            if ($b->getPrototype()->getTemp()){
                $this->log->debug("Destroying building <info>{$b->getPrototype()->getLabel()}</info> as it is a temp building.");
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackDestroyBuilding($town, $b));
                $b->setComplete(false)->setAp(0);
            }
            $b->setTempDefenseBonus(0);
        }
    }

    private function stage3_status(Town $town) {
        $this->log->info('<info>Processing status changes</info> ...');

        $status_survive   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy( ['name' => 'hsurvive'] );
        $status_hasdrunk  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy( ['name' => 'hasdrunk'] );
        $status_infection = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy( ['name' => 'infection'] );
        $status_camping   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy( ['name' => 'camper'] );

        $status_wound_infection = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy( ['name' => 'tg_meta_winfect'] );

        $status_clear_list = ['hasdrunk','haseaten','immune','hsurvive','drunk','drugged','healed','hungover','tg_dice','tg_cards','tg_clothes','tg_teddy','tg_guitar','tg_sbook','tg_steal','tg_home_upgrade','tg_hero','tg_chk_forum','tg_chk_active', 'tg_chk_workshop', 'tg_chk_build', 'tg_chk_movewb', 'tg_hide','tg_tomb', 'tg_home_clean', 'tg_home_shower', 'tg_home_heal_1', 'tg_home_heal_2', 'tg_home_defbuff', 'tg_rested', 'tg_shaman_heal', 'tg_ghoul_eat', 'tg_no_hangover', 'tg_ghoul_corpse', 'tg_betadrug', 'tg_build_vote', 'tg_insurrection'];

        $aliveCitizenInTown = 0;
        $aliveCitizen = 0;
        foreach ($town->getCitizens() as $citizen) {

            if ($citizen->getDailyUpgradeVote()) {
                $this->cleanup[] = $citizen->getDailyUpgradeVote();
                $citizen->setDailyUpgradeVote(null);
            }

            if ($citizen->getBuildingVote()) {
                $this->cleanup[] = $citizen->getBuildingVote();
                $citizen->setBuildingVote(null);
            }

            $citizen->getExpeditionRoutes()->clear();

            if (!$citizen->getAlive()) continue;

            $aliveCitizen++;
            $citizen->setHasSeenGazette(false);

            if($citizen->getZone() === null)
                $aliveCitizenInTown++;

            if ($citizen->getStatus()->contains($status_survive))
                $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is <info>protected</info> by <info>{$status_survive->getLabel()}</info>.");
            else
            {
                if (!$citizen->getStatus()->contains($status_hasdrunk)) {
                    $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>not</info> drunk today. <info>Increasing</info> thirst level.");
                    $this->citizen_handler->increaseThirstLevel( $citizen );
                }
                if (!$citizen->getStatus()->contains($status_infection) && $this->citizen_handler->isWounded( $citizen ) && !in_array( $citizen->getId(), $this->skip_infection )) {
                    $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is <info>wounded</info>. Adding an <info>infection</info>.");
                    $this->citizen_handler->inflictStatus($citizen, $status_wound_infection);
                }
            }

            if ($citizen->hasRole('ghoul')) {
                $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is a <info>ghoul</info>. <info>Increasing</info> hunger.");
                $citizen->setGhulHunger( $citizen->getGhulHunger() + (($town->getChaos() || $town->getDevastated()) ? 15 : 25));
            }

            $this->log->debug("Setting appropriate camping status for citizen <info>{$citizen->getUser()->getUsername()}</info> (who is <info>" . ($citizen->getZone() ? 'outside' : 'inside') . "</info> the town)...");
            if ($citizen->getZone()) {
                $citizen->addStatus( $status_camping );
                $citizen->setCampingTimestamp(0);
                $citizen->setCampingChance(0);
            }
            else $citizen->removeStatus( $status_camping );

            $this->log->debug("Removing volatile counters for citizen <info>{$citizen->getUser()->getUsername()}</info>...");
            $citizen->setWalkingDistance(0);
            $this->citizen_handler->setAP($citizen,false,$this->citizen_handler->getMaxAP( $citizen ),0);
            $this->citizen_handler->setBP($citizen,false,$this->citizen_handler->getMaxBP( $citizen ),0);
            $this->citizen_handler->setPM($citizen,false,$this->citizen_handler->getMaxPM( $citizen ));
            $citizen->getActionCounters()->clear();
            $citizen->getDigTimers()->clear();
            if ($citizen->getEscortSettings()) $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
            
            foreach ($this->entity_manager->getRepository( EscapeTimer::class )->findAllByCitizen( $citizen ) as $et)
                $this->cleanup[] = $et;
            foreach ($this->entity_manager->getRepository( DigRuinMarker::class )->findAllByCitizen( $citizen ) as $drm)
                $this->cleanup[] = $drm;
            $add_hangover = ($this->citizen_handler->hasStatusEffect($citizen, 'drunk') && !$this->citizen_handler->hasStatusEffect($citizen, 'tg_no_hangover'));
            foreach ($citizen->getStatus() as $st)
                if (in_array($st->getName(),$status_clear_list)) {
                    $this->log->debug("Removing volatile status from citizen <info>{$citizen->getUser()->getUsername()}</info>: <info>{$st->getLabel()}</info>.");
                    $this->citizen_handler->removeStatus( $citizen, $st );
                }
            if ($add_hangover) $this->citizen_handler->inflictStatus($citizen, 'hungover');

        }

        if($town->getDevastated()){
            $this->log->debug("Town is devastated, nothing to do.");
        } else {
            $this->log->debug("Town is not yet devastated, and has <info>$aliveCitizen</info> alive citizens (including <info>$aliveCitizenInTown</info> in town)");

            if ($aliveCitizen > 0 && $aliveCitizen <= 10 && $aliveCitizenInTown > 0 && !$town->getDevastated() && $town->getDay() > 3) {
                $this->log->debug("There is <info>$aliveCitizen</info> citizens alive, the town is not devastated, setting the town to <info>chaos</info> mode");
                $town->setChaos(true);

                foreach ($town->getCitizens() as $target_citizen)
                    $target_citizen->setBanished(false);
            }

            if ($aliveCitizenInTown == 0) {
                $this->log->debug("There is <info>$aliveCitizenInTown</info> citizens alive AND in town, setting the town to <info>devastated</info> mode and to <info>chaos</info> mode");

                if($town->getDay() >= 5){
                    $this->log->debug('Town has lived for 5 days or more, we give the <info>Last Man Standing</info> picto to a lucky citizen that died in town');
                    $citizen_eligible = [];
                    foreach ($town->getCitizens() as $citizen) {
                        if($citizen->getAlive() || $citizen->getZone())
                            continue;

                        if($citizen->getSurvivedDays() < $town->getDay())
                            continue;

                        if($citizen->getCauseOfDeath()->getRef() !== CauseOfDeath::NightlyAttack && $citizen->getCauseOfDeath()->getRef() !== CauseOfDeath::Radiations)
                            continue;
                        $citizen_eligible[] = $citizen;
                    }

                    $last_stand_picto = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_FEATURE_LAST_DEATH, 'r_surlst_#00');
                    if($last_stand_picto && count($citizen_eligible) > 0) {
                        /** @var Citizen $winner */
                        $winner = $this->random->pick($citizen_eligible);
                        $this->log->debug("We give the picto <info>$last_stand_picto</info> to the lucky citizen {$winner->getUser()->getUsername()}");
                        $this->picto_handler->give_validated_picto($winner, $last_stand_picto);
                    }

                    foreach ($citizen_eligible as $citizen)
                        $this->picto_handler->give_validated_picto($citizen, "r_surgrp_#00");

                }

                $town->setDevastated(true);
                $town->setChaos(true);
                $town->setDoor(true);

                $gazette = $town->findGazette($town->getDay());

                $townTemplate = $this->entity_manager->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gazetteTownLastAttack']);
                $news = new GazetteLogEntry();
                $news->setDay($town->getDay())->setGazette($gazette)->setLogEntryTemplate($townTemplate)->setVariables(['town' => $town->getName()]);
                $this->entity_manager->persist($news);

                foreach ($town->getCitizens() as $target_citizen)
                    $target_citizen->setBanished(false);

                foreach ($town->getBuildings() as $target_building)
                    if (!$target_building->getComplete()) $target_building->setAp(0);
            }
        }
    }

    private function stage3_zones(Town &$town) {
        $this->log->info('<info>Processing changes in the World Beyond</info> ...');

        $this->log->debug('Spreading zombies ...');
        $this->zone_handler->dailyZombieSpawn($town);

        $research_tower = $this->town_handler->getBuilding($town, 'small_gather_#02', true);
        $watchtower     = $this->town_handler->getBuilding($town, 'item_tagger_#00',  true);

        $gazette = $town->findGazette($town->getDay());

        if ($watchtower) switch ($watchtower->getLevel()) {
            case 0: $discover_range  = 0;  break;
            case 1: $discover_range  = 3;  break;
            case 2: $discover_range  = 6;  break;
            default: $discover_range = 10; break;
        } else $discover_range = 0;

        if ($research_tower) switch ($research_tower->getLevel()) {
            case 0: $recovery_chance  = 0.25; break;
            case 1: $recovery_chance  = 0.37; break;
            case 2: $recovery_chance  = 0.49; break;
            case 3: $recovery_chance  = 0.61; break;
            case 4: $recovery_chance  = 0.73; break;
            default: $recovery_chance = 0.85; break;
        } else $recovery_chance = 0.25;

        $wind = $this->random->pick( [Zone::DirectionNorthWest, Zone::DirectionNorth, Zone::DirectionNorthEast, Zone::DirectionWest, Zone::DirectionEast, Zone::DirectionSouthWest, Zone::DirectionSouth, Zone::DirectionSouthEast] );

        $this->log->debug('Processing individual zones ...');
        $this->log->debug('Modifiers - <info>Search Tower</info>: <info>' . ($research_tower ? $research_tower->getLevel() : 'No') . '</info>, <info>Watch Tower</info>: <info>' . ($watchtower ? $watchtower->getLevel() : 'No') . '</info>' );
        $this->log->debug("Wind Direction is <info>{$wind}</info>." );

        if ($research_tower)
            $gazette->setWindDirection($wind);

        $this->entity_manager->persist($gazette);

        $reco_counter = [0,0];

        $maze_zeds = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_EXPLORABLES_ZOMBIES_DAY, 5);
        $wind_dist = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WIND_DISTANCE, 2);

        foreach ($town->getZones() as $zone) {
            /** @var Zone $zone */

            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                foreach ($zone->getExplorerStats() as $ex) {
                    $ex->getCitizen()->removeExplorerStat( $ex );
                    $this->entity_manager->remove($ex);
                }

                foreach ($zone->getChatSilenceTimers() as $timer) {
                    $zone->removeChatSilenceTimer($timer);
                    $this->entity_manager->remove($timer);
                }
                $this->maze->populateMaze( $zone, $maze_zeds, true, true );
            }

            $distance = sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) );
            if ($zone->getCitizens()->count() || round($distance) <= $discover_range) {
                if ($zone->getDiscoveryStatus() !== Zone::DiscoveryStateCurrent) {
                    $this->log->debug( "Zone <info>{$zone->getX()}/{$zone->getY()}</info>: Set discovery state to <info>current</info>." );
                    $zone->setDiscoveryStatus(Zone::DiscoveryStateCurrent);
                    $zone->setZombieStatus( Zone::ZombieStateEstimate );
                }
            } elseif ($zone->getDiscoveryStatus() === Zone::DiscoveryStateCurrent) {
                $this->log->debug( "Zone <info>{$zone->getX()}/{$zone->getY()}</info>: Set discovery state to <info>past</info>." );
                $zone->setDiscoveryStatus(Zone::DiscoveryStatePast);
                $zone->setZombieStatus( Zone::ZombieStateUnknown );
            }

            if ($zone->getDirection() === $wind && round($distance) > $wind_dist) {
                $reco_counter[1]++;
                if ($this->random->chance( $recovery_chance )) {
                    $digs = mt_rand(5, 10);
                    $zone->setDigs( min( $zone->getDigs() + $digs, 25 ) );
                    $this->log->debug( "Zone <info>{$zone->getX()}/{$zone->getY()}</info>: Recovering by <info>{$digs}</info> to <info>{$zone->getDigs()}</info>." );
                    $reco_counter[0]++;
                }

                if ($zone->getPrototype() && $this->random->chance( $recovery_chance ) ) {
                    $rdigs = mt_rand(1, 5);
                    $zone->setRuinDigs( min( $zone->getRuinDigs() + $rdigs, 10 ) );
                    $this->log->debug( "Zone <info>{$zone->getX()}/{$zone->getY()}</info>: Recovering ruin by <info>{$rdigs}</info> to <info>{$zone->getRuinDigs()}</info>." );
                }
            }

            if ($zone->getImprovementLevel() > 0) {
              $zone->setImprovementLevel(max(($zone->getImprovementLevel() - 3), 0));
              $this->log->debug( "Zone <info>{$zone->getX()}/{$zone->getY()}</info>: Improvement Level has been reduced to <info>{$zone->getImprovementLevel()}</info>." );
            }
        }
        $this->log->debug("Recovered <info>{$reco_counter[0]}</info>/<info>{$reco_counter[1]}</info> zones." );

        if($this->conf->getTownConfiguration($town)->get( TownConf::CONF_FEATURE_SHAMAN_MODE, 'normal' ) == 'normal') {
            $this->log->debug("Processing <info>souls</info> mutations.");
            foreach ($this->zone_handler->getSoulZones($town) as $zone) {
                foreach ($zone->getFloor()->getItems() as $item) {
                    /** @var Item $item */
                    if($item->getPrototype()->getName() !== 'soul_blue_#00') continue;
                    if($this->random->chance(0.25)){
                        $this->log->debug("Mutating soul in zone [<info>{$zone->getX()},{$zone->getY()}</info>].");
                        $this->inventory_handler->forceRemoveItem($item);
                        $redsoul = $this->item_factory->createItem('soul_red_#00');
                        if($item->getFirstPick())
                            $redsoul->setFirstPick($item->getFirstPick());
                            
                        $this->inventory_handler->forceMoveItem($zone->getFloor(), $redsoul);
                        $this->entity_manager->persist($zone);

                    }
                }
            }

            foreach ($town->getBank()->getItems() as $item) {
                if($item->getPrototype()->getName() !== 'soul_blue_#00') continue;
                // In the bank, the count is > 1, we must loop through each soul
                for($i = 0 ; $i < $item->getCount() ; $i++) {
                    if($this->random->chance(0.1)){
                        $this->log->debug("Mutating soul in bank.");
                        $this->inventory_handler->forceRemoveItem($item);
                        $this->inventory_handler->forceMoveItem($town->getBank(), $this->item_factory->createItem('soul_red_#00'));
                    }
                }
            }
    
            foreach ($town->getCitizens() as $citizen) {
                foreach ($citizen->getHome()->getChest()->getItems() as $item) {
                    if($item->getPrototype()->getName() !== 'soul_blue_#00') continue;
                    if($this->random->chance(0.1)){
                        $this->log->debug("Mutating soul in chest of citizen <info>{$citizen->getUser()->getUsername()}</info>");
                        $this->inventory_handler->forceRemoveItem($item);
                        $this->inventory_handler->forceMoveItem($citizen->getHome()->getChest(), $this->item_factory->createItem('soul_red_#00'));
                        $this->entity_manager->persist($citizen->getHome()->getChest());
                    }
                }
                foreach ($citizen->getInventory()->getItems() as $item) {
                    if($item->getPrototype()->getName() !== 'soul_blue_#00') continue;
                    if($this->random->chance(0.1)){
                        $this->log->debug("Mutating soul in rucksack of citizen <info>{$citizen->getUser()->getUsername()}</info>");
                        $this->inventory_handler->forceRemoveItem($item);
                        $this->inventory_handler->forceMoveItem($citizen->getHome()->getChest(), $this->item_factory->createItem('soul_red_#00'));
                        $this->entity_manager->persist($citizen->getHome()->getChest());
                    }
                }
            }
        }
    }

    private function stage3_items(Town $town) {
        $this->log->info('<info>Processing item changes</info> ...');

        /** @var Inventory[] $inventories */
        $inventories = [];

        $inventories[] = $town->getBank();

        foreach ($town->getCitizens() as $citizen) {
            $inventories[] = $citizen->getInventory();
            $inventories[] = $citizen->getHome()->getChest();
        }

        foreach ($town->getZones() as $zone) {
            $inventories[] = $zone->getFloor();
            foreach ($zone->getRuinZones() as $ruinZone) {
                $inventories[] = $ruinZone->getFloor();
                if ($ruinZone->getRoomFloor()) $inventories[] = $ruinZone->getRoomFloor();
            }
        }

        $c = count($inventories);
        $this->log->debug( "Number of inventories: <info>{$c}</info>." );

        $morph = [
            'torch_#00'    => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'torch_off_#00']),
            'lamp_on_#00'  => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'lamp_#00']),
            'radio_on_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'radio_off_#00']),
            'tamed_pet_off_#00'  => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_#00']),
            'tamed_pet_drug_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_#00']),
            'maglite_2_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'maglite_1_#00']),
            'maglite_1_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'maglite_off_#00']),
        ];

        foreach ($morph as $source => $target) {
            $items = $this->inventory_handler->fetchSpecificItems($inventories, [(new ItemRequest($source))->fetchAll(true)]);

            $c = count($items);
            $this->log->debug( "Morphing <info>{$c}</info> items to type '<info>{$target->getLabel()}</info>'." );

            foreach ($items as $item)
                $item->setPrototype( $target );
        }
    }

    private function stage2_building_effects(Town $town) {
        $this->log->info('<info>Processing building functions</info> ...');

        $buildings = []; $max_votes = -1;
        foreach ($town->getBuildings() as $b) if ($b->getComplete())
            if ($b->getPrototype()->getMaxLevel() > 0 && $b->getPrototype()->getMaxLevel() > $b->getLevel()) {
                $v = $b->getDailyUpgradeVotes()->count();
                $this->log->debug("<info>{$v}</info> citizens voted for <info>{$b->getPrototype()->getLabel()}</info>.");
                if ($v > $max_votes) {
                    $buildings = [$b];
                    $max_votes = $v;
                } elseif ($v === $max_votes) $buildings[] = $b;
            }

        $spawn_default_blueprint = $this->town_handler->getBuilding($town, 'small_refine_#01', true) !== null;

        if (!empty($buildings)) {
            /** @var Building $target_building */
            $target_building = $this->random->pick( $buildings );
            $target_building->setLevel( $target_building->getLevel() + 1 );
            $this->log->debug("Increasing level of <info>{$target_building->getPrototype()->getLabel()}</info> to Level <info>{$target_building->getLevel()}</info>.");

            switch ($target_building->getPrototype()->getName()) {
                case 'small_gather_#00':
                    $def_add = [0,13,21,32,33,51];
                    $target_building->setDefenseBonus( $target_building->getDefenseBonus() + $def_add[ $target_building->getLevel() ] );
                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Increasing variable defense by <info>{$def_add[ $target_building->getLevel() ] }</info>.");
                    break;
                case 'small_water_#00':
                    $water_add = [5,20,20,30,30,40];
                    $town->setWell( $town->getWell() + $water_add[$target_building->getLevel()] );
                    $this->entity_manager->persist( $this->logTemplates->nightlyAttackUpgradeBuildingWell( $target_building, $water_add[$target_building->getLevel()] ) );

                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Increasing well count by <info>{$water_add[ $target_building->getLevel() ] }</info>.");
                    break;
                case 'small_refine_#01':
                    $spawn_default_blueprint = false;
                    $bps = [
                        ['bplan_c_#00' => 1],
                        ['bplan_c_#00' => 4],
                        ['bplan_c_#00' => 2,'bplan_u_#00' => 2],
                        ['bplan_u_#00' => 2,'bplan_r_#00' => 2],
                    ];
                    $opt_bp = [null,'bplan_c_#00','bplan_r_#00','bplan_e_#00'];

                    $plans = [];
                    foreach ($bps[$target_building->getLevel()] as $id => $count)
                        for ($i = 0; $i < $count; $i++) $plans[] = $this->item_factory->createItem( $id );
                    if ( $opt_bp[$target_building->getLevel()] !== null && $this->random->chance( 0.5 ) )
                        $plans[] = $this->item_factory->createItem( $opt_bp[$target_building->getLevel()] );

                    $tx = [];
                    foreach ($plans as $plan) {
                        $this->inventory_handler->forceMoveItem( $town->getBank(), $plan );
                        $tx[] = "<info>{$plan->getPrototype()->getLabel()}</info>";
                    }

                    $this->entity_manager->persist( $this->logTemplates->nightlyAttackUpgradeBuildingItems( $target_building, array_map( function(Item $e) { return  array($e->getPrototype()) ;}, $plans ) ));
                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Placing " . implode(', ', $tx) . " in the bank.");
                    break;
                case 'item_home_def_#00':
                    $def_add = [0,30,35,50,65,80];
                    $target_building->setDefenseBonus( $target_building->getDefenseBonus() + $def_add[ $target_building->getLevel() ] );
                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Increasing variable defense by <info>{$def_add[ $target_building->getLevel() ] }</info>.");
                    break;
                case 'item_tube_#00':
                    $def_mul = [0, 0.8, 1.6, 2.4, 3.2, 4];
                    $target_building->setDefenseBonus( $target_building->getDefense() * $def_mul[ $target_building->getLevel() ] );
                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Increasing variable defense by <info>{$def_mul[ $target_building->getLevel() ] }</info>.");
                    break;
            }
        }

        $watertower = $this->town_handler->getBuilding( $town, 'item_tube_#00', true );
        if ($watertower && $watertower->getLevel() > 0) {
            $n = [0,2,4,6,9,12];
            if ($town->getWell() >= $n[ $watertower->getLevel() ]) {
                $town->setWell( $town->getWell() - $n[ $watertower->getLevel() ] );
                $gazette = $town->findGazette( $town->getDay() );
                $gazette->setWaterlost($gazette->getWaterlost() + $n[$watertower->getLevel()]);
                $this->entity_manager->persist($gazette);
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackBuildingDefenseWater( $watertower, $n[ $watertower->getLevel() ] ) );
                $this->log->debug( "Deducting <info>{$n[$watertower->getLevel()]}</info> water from the well to operate the <info>{$watertower->getPrototype()->getLabel()}</info>." );
            } else {
                $watertower->setTempDefenseBonus(0 - $watertower->getDefense() - $watertower->getDefenseBonus());
            }
        }

        $daily_items = []; $tx = [];
        if ($spawn_default_blueprint) {
            $this->entity_manager->persist( $this->logTemplates->nightlyAttackProductionBlueprint( $town, $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'bplan_c_#00']) ) );
            $daily_items['bplan_c_#00'] = 1;
        }

        $has_fertilizer = $this->town_handler->getBuilding( $town, 'item_digger_#00', true ) !== null;

        $db = [
            'small_appletree_#00'      => [ 'apple_#00' => mt_rand(3,5) ],
            'item_vegetable_tasty_#00' => [ 'vegetable_#00' => !$has_fertilizer ? mt_rand(4,8) : mt_rand(6,8), 'vegetable_tasty_#00' => !$has_fertilizer ? mt_rand(0,2) : mt_rand(3,5) ],
            'item_bgrenade_#01'        => [ 'boomfruit_#00' => !$has_fertilizer ? mt_rand(3,7) : mt_rand(5,8) ],
            'small_chicken_#00'        => [ 'egg_#00' => 3 ],
        ];

        foreach ($db as $b_class => $spawn)
            if (($b = $this->town_handler->getBuilding( $town, $b_class, true )) !== null) {
                $local = [];
                foreach ( $spawn as $item_id => $count ) {
                    if (!isset($daily_items[$item_id])) $daily_items[$item_id] = $count;
                    else $daily_items[$item_id] += $count;
                    if ($count > 0) $local[] = ['item' => $item_id, 'count' => $count];
                }
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackProduction( $b, array_map( function($e) {
                    return [ 'item' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($e['item']), 'count' => $e['count'] ];
                }, $local ) ) );
            }


        foreach ($daily_items as $item_id => $count)
            for ($i = 0; $i < $count; $i++) {
                $item = $this->item_factory->createItem( $item_id );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $item );
                $tx[] = "<info>{$item->getPrototype()->getLabel()}</info>";
            }

        if (!empty($daily_items))
            $this->log->debug("Daily items: Placing " . implode(', ', $tx) . " in the bank.");
    }

    private function stage3_pictos(Town $town){

        $status_camping           = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'camper']);
        $picto_camping            = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_camp_#00']);
        $picto_camping_devastated = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_cmplst_#00']);
        $picto_nightwatch         = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_guard_#00']);
        $this->log->info('<info>Processing Pictos functions</info> ...');

        // Marking pictos as obtained not-today
        $citizens = $town->getCitizens();
        foreach ($citizens as $citizen) {
            // If the citizen is not alive anymore, the calculation is not to be done here
            if(!$citizen->getAlive())
                continue;

            // Giving picto camper if he camped
            if ($citizen->getStatus()->contains($status_camping)) {
                if ($town->getDevastated() && $town->getDay() >= 10){
                    $this->picto_handler->give_picto($citizen, $picto_camping_devastated);
                } else {
                    $this->picto_handler->give_picto($citizen, $picto_camping);
                }
            }

            // Giving picto nightwatch if he's watcher
            $watch = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchOfCitizenForADay($citizen, $town->getDay() - 1);
            if($watch !== null){
                $this->picto_handler->give_picto($citizen, $picto_nightwatch);
            }

            $this->picto_handler->nightly_validate_picto( $citizen );
        }
    }

    private function stage3_roles(Town $town){
        if($town->getChaos()) {
            $this->log->info( "Town is in <info>Chaos</info>, no more votes." );
            return;
        }

        $this->log->info( "Processing elections..." );
        $citizens = $town->getCitizens();
        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();
        $votes = array();

        foreach ($roles as $role) {
            /** @var CitizenRole $role */
            $special_vote = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => 'special_vote_' . $role->getName()]);

            /** @var Citizen $last_one */
            $last_one = $this->entity_manager->getRepository(Citizen::class)->findLastOneByRoleAndTown($role, $town);
            if ($last_one &&
                ($last_one->getAlive() ||                                                                                                               // Skip vote if the last citizen with this role is still alive
                    ($last_one->getSurvivedDays() >= ($town->getDay()-1)) ||                                                                            // Skip vote if the last citizen with this role died during the attack
                    ($last_one->getSurvivedDays() >= ($town->getDay()-2) && $last_one->getCauseOfDeath()->getRef() !== CauseOfDeath::NightlyAttack)     // Skip vote if the last citizen with this role died the previous day
                )) continue;

            // Getting vote per role per citizen
            $votes[$role->getId()] = array();
            foreach ($citizens as $citizen) {
                if($citizen->getAlive()) {
                    $votes[$role->getId()][$citizen->getId()] = $this->entity_manager->getRepository(CitizenVote::class)->countCitizenVotesFor($citizen, $role);
                }
            }

            foreach ($citizens as $citizen) {
                // Removing citizen with 0 votes
                if(array_key_exists($role->getId(), $votes)
                    && array_key_exists($citizen->getId(), $votes[$role->getId()])
                    && $votes[$role->getId()][$citizen->getId()] == 0) {
                    unset($votes[$role->getId()][$citizen->getId()]);
                }
            }

            if(empty($votes[$role->getId()])) {
                foreach ($citizens as $citizen) {
                    if($citizen->getAlive()) {
                        $votes[$role->getId()][$citizen->getId()] = 0;
                    }
                }
            }

            foreach ($citizens as $citizen) {
                if(!$citizen->getAlive()) continue;

                $voted = ($this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($citizen, $role) !== null);
                if(!$voted) {
                    // He has not voted, let's give his vote to someone who has votes
                    $vote_for_id = $this->random->pick(array_keys($votes[$role->getId()]), 1);

                    if(isset($votes[$role->getId()][$vote_for_id]))
                        $votes[$role->getId()][$vote_for_id]++;
                    else
                        $votes[$role->getId()][$vote_for_id] = 1;
                }
            }

            // Let's get the winner
            $citizenWinnerId = 0;
            $citizenWinnerCount = 0;

            foreach ($votes[$role->getId()] as $idCitizen => $count) {
                if($citizenWinnerCount <= $count) {
                    $citizenWinnerCount = $count;
                    $citizenWinnerId = $idCitizen;
                }
            }

            // We give him the related status
            $winningCitizen = $this->entity_manager->getRepository(Citizen::class)->find($citizenWinnerId);
            $this->log->info( "Citizen <info>{$winningCitizen->getUser()->getUsername()}</info> has been elected as <info>{$role->getLabel()}</info>." );
            if($winningCitizen !== null){
                $this->citizen_handler->addRole($winningCitizen, $role);
                $this->citizen_handler->setPM($winningCitizen, false, $this->citizen_handler->getMaxPM($winningCitizen));
                if($role->getName() == "shaman")
                    $this->citizen_handler->inflictStatus($winningCitizen, "tg_shaman_immune"); // Shaman is immune to red souls
                $this->entity_manager->persist($winningCitizen);
            }

            // we remove the votes
            $votes = $this->entity_manager->getRepository(CitizenVote::class)->findBy(['role' => $role]);
            foreach ($votes as $vote) {
                $this->entity_manager->remove($vote);
            }

            // we remove the ability to vote from the WB
            foreach ($citizens as $citizen) {
                /** @var Citizen $citizen */
                if($citizen->getSpecialActions()->contains($special_vote)) {
                    $citizen->removeSpecialAction($special_vote);
                    $this->entity_manager->persist($citizen);
                }
            }
        }
    }

    public function advance_day(Town $town, EventConf $event): bool {
        $this->skip_reanimation = [];

        $this->log->info( "Nightly attack request received for town <info>{$town->getId()}</info> (<info>{$town->getName()}</info>)." );
        if (!$this->check_town($town)) {
            $this->log->info("Precondition failed. Attack is <info>cancelled</info>.");
            $town->setDayWithoutAttack($town->getDayWithoutAttack() + 1);
            return false;
        } else $this->log->info("Precondition checks passed. Attack can <info>commence</info>.");

        $event->hook_nightly_pre($town);

        $this->town_handler->triggerAlways( $town );

        $this->log->info('Entering <comment>Phase 1</comment> - Pre-attack processing');
        $this->stage1_prepare($town);
        $this->stage1_vanish($town);
        $this->stage1_status($town);

        $town->setDay( $town->getDay() + 1);
        $this->log->info('Entering <comment>Phase 2</comment> - The Attack');
        $this->stage2_pre_attack_buildings($town);
        $this->stage2_building_effects($town);
        $this->stage2_day($town);
        $this->stage2_surprise_attack($town);
        $this->stage2_attack($town);
        $this->stage2_post_attack_buildings($town);

        $this->log->info('Entering <comment>Phase 3</comment> - Dawn of a New Day');
        $this->stage3_status($town);
        $this->stage3_roles($town);
        $this->stage3_zones($town);
        $this->stage3_items($town);
        $this->stage3_pictos($town);

        $event->hook_nightly_post($town);

        TownRankingProxy::fromTown( $town, true );
        foreach ($town->getCitizens() as $citizen) CitizenRankingProxy::fromCitizen( $citizen, true );

        $c = count($this->cleanup);
        $this->log->info("It is now <comment>Day {$town->getDay()}</comment> in <info>{$town->getName()}</info>.");
        $this->town_handler->calculate_zombie_attacks( $town, 3 );
        $this->log->debug( "<info>{$c}</info> entities have been marked for removal." );
        $this->log->info( "<comment>Script complete.</comment>" );

        return true;
    }

    public function get_cleanup_container() {
        $cc = $this->cleanup;
        $this->cleanup = [];
        return $cc;
    }
}
