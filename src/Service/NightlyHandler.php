<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenStatus;
use App\Entity\DigRuinMarker;
use App\Entity\EscapeTimer;
use App\Entity\Inventory;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Structures\ItemRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NightlyHandler
{
    private $cleanup = [];
    private $skip_reanimation = [];

    private $entity_manager;
    private $log;
    private $citizen_handler;
    private $random;
    private $death_handler;
    private $town_handler;
    private $zone_handler;
    private $inventory_handler;
    private $picto_handler;
    private $item_factory;
    private $logTemplates;

  public function __construct(EntityManagerInterface $em, LoggerInterface $log, CitizenHandler $ch, InventoryHandler $ih,
                                RandomGenerator $rg, DeathHandler $dh, TownHandler $th, ZoneHandler $zh, PictoHandler $ph, ItemFactory $if, LogTemplateHandler $lh)
    {
        $this->entity_manager = $em;
        $this->citizen_handler = $ch;
        $this->death_handler = $dh;
        $this->inventory_handler = $ih;
        $this->citizen_handler->upgrade($dh);
        $this->random = $rg;
        $this->town_handler = $th;
        $this->zone_handler = $zh;
        $this->picto_handler = $ph;
        $this->item_factory = $if;
        $this->log = $log;
        $this->logTemplates = $lh;
    }

    private function check_town(Town &$town): bool {
        if ($town->isOpen()) {
            $this->log->debug('The town lobby is <comment>open</comment>!');
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

        return true;
    }

    private function kill_wrap( Citizen &$citizen, CauseOfDeath &$cod, bool $skip_reanimation = false, int $zombies = 0, $skip_log = false ) {
        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> dies of <info>{$cod->getLabel()}</info>.");
        $this->death_handler->kill($citizen,$cod,$rr);

        if (!$skip_log) $this->entity_manager->persist( $this->logTemplates->citizenDeath( $citizen, $zombies ) );
        foreach ($rr as $r) $this->cleanup[] = $r;
        if ($skip_reanimation) $this->skip_reanimation[] = $citizen->getId();
    }

    private function stage1_vanish(Town &$town) {
        $this->log->info('<info>Vanishing citizens</info> ...');
        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::Vanished);

        $camp_1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_hide' );
        $camp_2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' );

        foreach ($town->getCitizens() as $citizen)
            if ($citizen->getAlive() && $citizen->getZone()) {

                $citizen_hidden = $citizen->getStatus()->contains( $camp_1 ) || $citizen->getStatus()->contains( $camp_2 );
                if ($citizen_hidden) {
                    // This poor soul wants to camp outside.
                    $survival_chance = $citizen->getCampingChance();

                    if (!$this->random->chance($survival_chance)) {
                        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> was at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> and died while camping (survival chance was " . ($survival_chance * 100) . "%)!");
                        $this->kill_wrap($citizen, $cod);
                    }
                    else {
                        $citizen->setCampingCounter($citizen->getCampingCounter() + 1);
                        $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> survived camping at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> with a survival chance of <info>" . ($survival_chance * 100) . "%</info>.");
                    }
                }
                else {
                  $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is at <info>{$citizen->getZone()->getX()}/{$citizen->getZone()->getY()}</info> without protection!");
                  $this->kill_wrap($citizen, $cod);
                }
            }
    }

    private function stage1_status(Town &$town) {
        $this->log->info('<info>Processing status-related deaths</info> ...');
        $cod_thirst = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::Dehydration);
        $cod_addict = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::Addiction);
        $cod_infect = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::Infection);

        $status_infected  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'infection' );
        $status_survive   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'hsurvive' );
        $status_thirst2   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'thirst2' );
        $status_drugged   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'drugged' );
        $status_addicted  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'addict' );

        foreach ($town->getCitizens() as $citizen) {

            if (!$citizen->getAlive()) continue;

            if ($citizen->getStatus()->contains( $status_survive )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> is <info>protected</info> by <info>{$status_survive->getLabel()}</info>." );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_thirst2 )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_thirst2->getLabel()}</info>." );
                $this->kill_wrap( $citizen, $cod_thirst, true );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_addicted ) && !$citizen->getStatus()->contains( $status_drugged )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_addicted->getLabel()}</info>, but not <info>{$status_drugged->getLabel()}</info>." );
                $this->kill_wrap( $citizen, $cod_addict, true );
                continue;
            }

            if ($citizen->getStatus()->contains( $status_infected )) {
                $this->log->debug( "Citizen <info>{$citizen->getUser()->getUsername()}</info> has <info>{$status_infected->getLabel()}</info>." );
                $chance = 0.5;
                // In Pandamonium town, there is 0.75 chance you die from infection
                if($town->getType()->getName() == 'panda')
                    $chance = 0.75;
                if ($this->random->chance(0.5)) $this->kill_wrap( $citizen, $cod_infect, true );
                continue;
            }
        }
    }

    private function stage2_day(Town &$town) {
        $this->log->info('<info>Updating survival information</info> ...');
        foreach ($town->getCitizens() as $citizen) {
            if (!$citizen->getAlive()) continue;
            $citizen->setSurvivedDays( $citizen->getTown()->getDay() );
        }
    }

    private function stage2_surprise_attack(Town &$town) {
        $this->log->info('<info>Awakening the dead</info> ...');
        /** @var Citizen[] $houses */
        $houses = [];
        /** @var Citizen[] $targets */
        $targets = [];
        /** @var Building[] $buildings */
        $buildings = [];

        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::NightlyAttack);

        foreach ($town->getCitizens() as $citizen) {
            if ($citizen->getAlive() && !$citizen->getZone())
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

        foreach ($houses as $id => $corpse) {

            $opts = [];
            if (!empty( $targets )) $opts[] = 1;
            if (!empty( $buildings )) $opts[] = 2;
            if ($town->getWell() > 0) $opts[] = 3;

            if (empty($opts)) {
                $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> has nothing to do.");
                $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackNothing( $corpse ) );
                continue;
            }

            switch ($this->random->pick($opts, 1)) {
                case 1:
                    $victim = array_pop($targets);
                    $this->log->debug("The corpse of citizen <info>{$corpse->getUser()->getUsername()}</info> attacks and kills <info>{$victim->getUser()->getUsername()}</info>.");
                    $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackKill( $corpse, $victim ) );
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
                    $this->entity_manager->persist( $this->logTemplates->nightlyInternalAttackWell( $corpse, $d ) );
                    $town->setWell( $town->getWell() - $d );
                    break;
            }
        }
    }

    private function stage2_attack(Town &$town) {
        $this->log->info('<info>Marching the horde</info> ...');
        $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef(CauseOfDeath::NightlyAttack);
        $status_terror  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'terror' );

        $has_kino = $this->town_handler->getBuilding($town, 'small_cinema_#00', true);

	    $def  = $this->town_handler->calculate_town_def( $town );
	    if($town->getDevastated())
	        $def = 0;
        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()-1);
        $zombies = $est ? $est->getZombies() : 0;

        $overflow = !$town->getDoor() ? max(0, $zombies - $def) : $zombies;
        $this->log->debug("The town has <info>{$def}</info> defense and is attacked by <info>{$zombies}</info> Zombies. The door is <info>" . ($town->getDoor() ? 'open' : 'closed') . "</info>!");
        $this->log->debug("<info>{$overflow}</info> Zombies have entered the town!");

        $this->entity_manager->persist( $this->logTemplates->nightlyAttackBegin($town, $zombies) );
        $this->entity_manager->persist( $this->logTemplates->nightlyAttackSummary($town, $town->getDoor(), $overflow) );

        if ($overflow <= 0) return;

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
        $attack_day = $town->getDay() - 1;

        if ($attack_day <= 5) {
            $attacking = $overflow;
            $targets = $this->random->pick($targets, mt_rand( ceil(count($targets) * 0.15), ceil(count($targets) * 0.35 )), true);
        } else {
            if     ($attack_day <= 14) $x = 1;
            elseif ($attack_day <= 18) $x = 4;
            elseif ($attack_day <= 23) $x = 5;
            else                       $x = 6;

            $attacking = min(($attack_day + $x) * max(10, count($targets)), $overflow);
            $targets = $this->random->pick($targets, ceil(count($targets) * 0.85), true);
        }

        $this->log->debug("<info>{$attacking}</info> Zombies are attacking <info>" . count($targets) . "</info> citizens!");
        $this->entity_manager->persist( $this->logTemplates->nightlyAttackLazy($town, $attacking) );

        $max = empty($targets) ? 0 : ceil(4 * $attacking/count($targets));

        $left = count($targets);
        foreach ($targets as $target) {
            $left--;
            $home = $target->getHome();
            if ($left <= 0) $force = $attacking;
            else $force = $attacking > 0 ? mt_rand(1, max(1,min($max,$attacking-$left)) ) : 0;
            $def = $this->town_handler->calculate_home_def($home);
            $this->log->debug("Citizen <info>{$target->getUser()->getUsername()}</info> is attacked by <info>{$force}</info> zombies and protected by <info>{$def}</info> home defense!");
            if ($force > $def){
                $this->kill_wrap($target, $cod, false, $force);
                // he dies from the attack, he validate the new day
                $target->setSurvivedDays($town->getDay());
            }
            else {
                $this->entity_manager->persist($this->logTemplates->citizenZombieAttackRepelled( $target, $force, $def));
                if (!$has_kino && $this->random->chance( 0.75 * ($force/max(1,$def)) )) {
                    $this->citizen_handler->inflictStatus( $target, $status_terror );
                    $this->log->debug("Citizen <info>{$target->getUser()->getUsername()}</info> now suffers from <info>{$status_terror->getLabel()}</info>");
                }
            }

            $attacking -= $force;
        }
    }

    private function stage3_status(Town &$town) {
        $this->log->info('<info>Processing status changes</info> ...');

        $status_survive   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'hsurvive' );
        $status_hasdrunk  = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'hasdrunk' );
        $status_infection = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'infection' );
        $status_camping   = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'camper' );

        $status_clear_list = ['hasdrunk','haseaten','immune','hsurvive','drugged','healed','hungover','tg_dice','tg_cards','tg_clothes','tg_teddy','tg_guitar','tg_sbook','tg_steal','tg_home_upgrade','tg_hero','tg_chk_forum','tg_chk_active', 'tg_hide','tg_tomb', 'tg_home_clean', 'tg_home_shower', 'tg_home_heal_1', 'tg_home_heal_2', 'tg_home_defbuff'];
        $status_morph_list = [
            'drunk' => $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'hungover' ),
        ];

        $aliveCitizenInTown = 0;

        foreach ($town->getCitizens() as $citizen) {

            if ($citizen->getDailyUpgradeVote()) {
                $this->cleanup[] = $citizen->getDailyUpgradeVote();
                $citizen->setDailyUpgradeVote(null);
            }

            $citizen->getExpeditionRoutes()->clear();
            if (!$citizen->getAlive()) continue;

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
                if (!$citizen->getStatus()->contains($status_infection) && $this->citizen_handler->isWounded( $citizen )) {
                    $this->log->debug("Citizen <info>{$citizen->getUser()->getUsername()}</info> is <info>wounded</info>. Adding an <info>infection</info>.");
                    $this->citizen_handler->inflictStatus($citizen, $status_infection);
                }
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
            $citizen->getActionCounters()->clear();
            $citizen->getDigTimers()->clear();
            foreach ($this->entity_manager->getRepository( EscapeTimer::class )->findAllByCitizen( $citizen ) as $et)
                $this->cleanup[] = $et;
            foreach ($this->entity_manager->getRepository( DigRuinMarker::class )->findAllByCitizen( $citizen ) as $drm)
                $this->cleanup[] = $drm;

            foreach ($citizen->getStatus() as $st)
                if (in_array($st->getName(),$status_clear_list)) {
                    $this->log->debug("Removing volatile status from citizen <info>{$citizen->getUser()->getUsername()}</info>: <info>{$st->getLabel()}</info>.");
                    $this->citizen_handler->removeStatus( $citizen, $st );
                }
            foreach ($citizen->getStatus() as $st)
                if (isset($status_morph_list[$st->getName()])) {
                    $this->log->debug("Morphing volatile status from citizen <info>{$citizen->getUser()->getUsername()}</info>: <info>{$st->getLabel()}</info> -> <info>{$status_morph_list[$st->getName()]->getLabel()}</info>.");
                    $this->citizen_handler->removeStatus( $citizen, $st );
                    $this->citizen_handler->inflictStatus( $citizen, $status_morph_list[$st->getName()] );
                }
        }

        if($town->getDay() > 3) {
            if($town->getDevastated()){
                $this->log->debug("Town is devastated, nothing to do.");
            } else {
                if ($aliveCitizenInTown > 0 && $aliveCitizenInTown <= 10 && !$town->getDevastated()) {
                    $this->log->debug("There is <info>$aliveCitizenInTown</info> citizens alive AND in town, the town is not devastated, setting the town to <info>chaos</info> mode");
                    $town->setChaos(true);
                } else if ($aliveCitizenInTown == 0) {
                    $this->log->debug("There is <info>$aliveCitizenInTown</info> citizens alive AND in town, setting the town to <info>devastated</info> mode and to <info>chaos</info> mode");
                    $town->setDevastated(true);
		            $town->setChaos(true);
		            $town->setDoor(true);
		            foreach ($town->getCitizens() as $target_citizen)
		                $target_citizen->setBanished(false);
		            foreach ($town->getBuildings() as $target_building)
		                if (!$target_building->getComplete()) $target_building->setAp(0);
                }
            }
        }
    }

    private function stage3_zones(Town &$town) {
        $this->log->info('<info>Processing changes in the World Beyond</info> ...');

        $this->log->debug('Spreading zombies ...');
        $this->zone_handler->dailyZombieSpawn($town);

        $research_tower = $this->town_handler->getBuilding($town, 'small_gather_#02', true);
        $watchtower     = $this->town_handler->getBuilding($town, 'item_tagger_#00',  true);

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

        $reco_counter = [0,0];
        foreach ($town->getZones() as $zone) {

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

            if ($zone->getDirection() === $wind && round($distance) > 2) {
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
    }

    private function stage3_items(Town &$town) {
        $this->log->info('<info>Processing item changes</info> ...');

        /** @var Inventory[] $inventories */
        $inventories = [];

        $inventories[] = $town->getBank();

        foreach ($town->getCitizens() as &$citizen) {
            $inventories[] = $citizen->getInventory();
            $inventories[] = $citizen->getHome()->getChest();
        }

        foreach ($town->getZones() as &$zone) {
            $inventories[] = $zone->getFloor();
        }

        $c = count($inventories);
        $this->log->debug( "Number of inventories: <info>{$c}</info>." );

        $morph = [
            'torch_#00'    => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('torch_off_#00'),
            'lamp_on_#00'  => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('lamp_#00'),
            'radio_on_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('radio_off_#00'),
            'tamed_pet_off_#00'  => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('tamed_pet_#00'),
            'tamed_pet_drug_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('tamed_pet_#00'),
        ];

        foreach ($morph as $source => $target) {
            $items = $this->inventory_handler->fetchSpecificItems($inventories, [(new ItemRequest($source))->fetchAll(true)]);

            $c = count($items);
            $this->log->debug( "Morphing <info>{$c}</info> items to type '<info>{$target->getLabel()}</info>'." );

            foreach ($items as &$item)
                $item->setPrototype( $target );
        }
    }

    private function stage3_buildings(Town &$town) {
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

                    $this->entity_manager->persist( $this->logTemplates->nightlyAttackUpgradeBuildingItems( $target_building, $plans ) );
                    $this->log->debug("Leveling up <info>{$target_building->getPrototype()->getLabel()}</info>: Placing " . implode(', ', $tx) . " in the bank.");
                    break;
            }
        }

        $watertower = $this->town_handler->getBuilding( $town, 'item_tube_#00', true );
        if ($watertower && $watertower->getLevel() > 0) {
            $n = [0,2,4,6,9,12];
            if ($town->getWell() >= $n[ $watertower->getLevel() ]) {
                $town->setWell( $town->getWell() - $n[ $watertower->getLevel() ] );
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackBuildingDefenseWater( $watertower, $n[ $watertower->getLevel() ] ) );
                $this->log->debug( "Deducting <info>{$n[$watertower->getLevel()]}</info> water from the well to operate the <info>{$watertower->getPrototype()->getLabel()}</info>." );
            }
        }

        $daily_items = []; $tx = [];
        if ($spawn_default_blueprint) {
            $this->entity_manager->persist( $this->logTemplates->nightlyAttackProductionBlueprint( $town, $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('bplan_c_#00') ) );
            $daily_items['bplan_c_#00'] = 1;
        }

        $has_fertilizer = $this->town_handler->getBuilding( $town, 'item_digger_#00', true ) !== null;

        $db = [
            'small_appletree_#00'      => [ 'apple_#00' => mt_rand(3,5) ],
            'item_vegetable_tasty_#00' => [ 'vegetable_#00' => !$has_fertilizer ? mt_rand(4,8) : mt_rand(8,14), 'vegetable_tasty_#00' => !$has_fertilizer ? mt_rand(0,2) : mt_rand(2,5) ],
            'item_bgrenade_#01'        => [ 'boomfruit_#00' => !$has_fertilizer ? mt_rand(3,5) : mt_rand(5,8) ],
            'small_chicken_#00'        => [ 'egg_#00' => 3 ],
        ];

        foreach ($db as $b_class => $spawn)
            if (($b = $this->town_handler->getBuilding( $town, $b_class, true )) !== null) {
                $local = [];
                foreach ( $spawn as $item_id => $count ) {
                    if (!isset($daily_items[$item_id])) $daily_items[$item_id] = $count;
                    else $daily_items[$item_id] += $count;
                    if ($count > 0) $local[$item_id] = $count;
                }
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackProduction( $b, array_map( function($proto,$count) {
                    return [ $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($proto), $count ];
                }, array_keys($local), $local ) ) );
            }


        foreach ($daily_items as $item_id => $count)
            for ($i = 0; $i < $count; $i++) {
                $item = $this->item_factory->createItem( $item_id );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $item );
                $tx[] = "<info>{$item->getPrototype()->getLabel()}</info>";
            }

        if (!empty($daily_items))
            $this->log->debug("Daily items: Placing " . implode(', ', $tx) . " in the bank.");

        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {
            if ($b->getPrototype()->getTemp()){
                $this->log->debug("Destroying building <info>{$b->getPrototype()->getLabel()}</info> as it is a temp building.");
                $this->entity_manager->persist( $this->logTemplates->nightlyAttackDestroyBuilding($town, $b));
                $b->setComplete(false)->setAp(0);
            }
            $b->setTempDefenseBonus(0);
        }
    }

    private function stage3_pictos(Town &$town){

        $status_camping           = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'camper' );
        $picto_camping            = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName( 'r_camp_#00' );
        $picto_camping_devastated = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName( 'r_cmplst_#00' );
        $this->log->info('<info>Processing Pictos functions</info> ...');
        // Marking pictos as obtained not-today
        $citizens = $town->getCitizens();
        foreach ($citizens as $citizen) {
            // If the citizen is not alive anymore, the calculation is not to be done here
            if(!$citizen->getAlive())
                continue;

            // Fetching picto obtained today
            $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findTodayPictoByUserAndTown($citizen->getUser(), $citizen->getTown());
            foreach ($pendingPictosOfUser as $pendingPicto) {
                $this->log->info("Citizen <info>{$citizen->getUser()->getUsername()}</info> has earned picto <info>{$pendingPicto->getPrototype()->getLabel()}</info>. It has persistance <info>{$pendingPicto->getPersisted()}</info>");

                $persistPicto = false;
                // In Small Towns, if the user has 100 soul points or more, he must survive at least 8 days or die from the attack during day 7 to 8
                // to validate the picto (set them as persisted)
                if($town->getType()->getName() == "small" && $citizen->getUser()->getSoulPoints() >= 100) {
                    $this->log->debug("This is a small town, and <info>{$citizen->getUser()->getUsername()}</info> has more that 100 soul points, we use the day 8 rule");
                    if($town->getDay() == 8 && $citizen->getCauseOfDeath() != null && $citizen->getCauseOfDeath()->getRef() == CauseOfDeath::NightlyAttack){
                        $persistPicto = true;
                    } else if  ($town->getDay() > 8) {
                        $persistPicto = true;
                    }
                } else {
                    $this->log->debug("We persist the pictos earned the previous days");
                    $persistPicto = true;
                }

                if(!$persistPicto)
                    continue;

                // We check if this picto has already been earned previously (such as Heroic Action, 1 per day)
                $pendingPreviousPicto = $this->entity_manager->getRepository(Picto::class)->findPreviousDaysPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pendingPicto->getPrototype());
                if($pendingPreviousPicto === null) {
                    $this->log->info("Setting persisted to 1");
                    // We do not have it, we set it as earned
                    $pendingPicto->setPersisted(1);
                    $this->entity_manager->persist($pendingPicto);
                } else {
                    // We have it, we add the count to the previously earned
                    // And remove the picto from today
                    $this->log->info("Merging with previously earned picto");
                    $pendingPreviousPicto->setCount($pendingPreviousPicto->getCount() + $pendingPicto->getCount());
                    $this->entity_manager->persist($pendingPreviousPicto);
                    $this->entity_manager->remove($pendingPicto);
                }
            }

            // Giving picto camper if he camped
            if ($citizen->getStatus()->contains($status_camping)) {
                if ($town->getDevastated() && $town->getDay() >= 10){
                    $this->picto_handler->give_picto($citizen, $picto_camping_devastated);
                } else {
                    $this->picto_handler->give_picto($citizen, $picto_camping);
                }
            }
        }
    }

    public function advance_day(Town &$town): bool {
        $this->skip_reanimation = [];

        $this->log->info( "Nightly attack request received for town <info>{$town->getId()}</info> (<info>{$town->getName()}</info>)." );
        if (!$this->check_town($town)) {
            $this->log->info("Precondition failed. Attack is <info>cancelled</info>.");
            return false;
        } else $this->log->info("Precondition checks passed. Attack can <info>commence</info>.");

        $this->town_handler->triggerAlways( $town );

        $this->log->info('Entering <comment>Phase 1</comment> - Pre-attack processing');
        $this->stage1_vanish($town);
        $this->stage1_status($town);

        $town->setDay( $town->getDay() + 1);
        $this->log->info('Entering <comment>Phase 2</comment> - The Attack');
        $this->stage2_day($town);
        $this->stage2_surprise_attack($town);
        $this->stage2_attack($town);

        $this->log->info('Entering <comment>Phase 3</comment> - Dawn of a New Day');
        $this->stage3_buildings($town);
        $this->stage3_status($town);
        $this->stage3_zones($town);
        $this->stage3_items($town);
        $this->stage3_pictos($town);

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
