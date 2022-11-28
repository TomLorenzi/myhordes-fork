<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\CitizenWatch;
use App\Entity\Complaint;
use App\Entity\HeroSkillPrototype;
use App\Entity\Item;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Town;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CitizenHandler
{
    private EntityManagerInterface $entity_manager;
    private ItemFactory $item_factory;
    private RandomGenerator $random_generator;
    private InventoryHandler $inventory_handler;
    private PictoHandler $picto_handler;
    private LogTemplateHandler $log;
    private ContainerInterface $container;
    private UserHandler $user_handler;
    private ConfMaster $conf;
    private GameProfilerService $gps;
    private CrowService $crow;

    public function __construct(EntityManagerInterface $em, RandomGenerator $g, InventoryHandler $ih,
                                PictoHandler $ph, ItemFactory $if, LogTemplateHandler $lh, ContainerInterface $c, UserHandler $uh,
                                ConfMaster $conf, GameProfilerService $gps, CrowService $crow )
    {
        $this->entity_manager = $em;
        $this->random_generator = $g;
        $this->inventory_handler = $ih;
        $this->picto_handler = $ph;
        $this->item_factory = $if;
        $this->log = $lh;
        $this->container = $c;
        $this->user_handler = $uh;
        $this->conf = $conf;
        $this->gps = $gps;
        $this->crow = $crow;
    }

    /**
     * @param Citizen $citizen
     * @param string|CitizenStatus|string[]|CitizenStatus[] $status
     * @param bool $all
     * @return bool
     */
    public function hasStatusEffect( Citizen $citizen, $status, bool $all = false ): bool {
        $status = array_map(function($s): string {
            /** @var $s string|CitizenStatus */
            if (is_a($s, CitizenStatus::class)) return $s->getName();
            elseif (is_string($s)) return $s;
            else return '???';
        }, is_array($status) ? $status : [$status]);

        if ($all) {
            foreach ($citizen->getStatus() as $s)
                if (!in_array($s->getName(), $status)) return false;
        } else {
            foreach ($citizen->getStatus() as $s)
                if (in_array($s->getName(), $status)) return true;
        }
        return $all;
    }

    /**
     * Returns true if a given citizen is wounded
     * @param Citizen $citizen
     * @return bool
     */
    public function isWounded(Citizen $citizen): bool {
        return $this->hasStatusEffect( $citizen, ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'], false );
    }

    public function inflictWound( Citizen &$citizen ): ?CitizenStatus {
        if ($this->isWounded($citizen)) return null;
        // $ap_above_6 = $citizen->getAp() - $this->getMaxAP( $citizen );
        $citizen->addStatus( $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName(
            $this->random_generator->pick( ['wound1','wound2','wound3','wound4','wound5','wound6'] )
        ) );
        $citizen->addStatus($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('tg_meta_wound'));
        // if ($ap_above_6 >= 0)
        //    $citizen->setAp( $this->getMaxAP( $citizen ) + $ap_above_6 );

        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_wound_#00');
        $this->picto_handler->give_picto($citizen, $pictoPrototype);
        return $status;
    }

    public function healWound( Citizen &$citizen ) {
        foreach ($citizen->getStatus() as $status)
            if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] ))
                $citizen->removeStatus( $status );
    }

    /**
     * @param Citizen $citizen
     * @param string|CitizenStatus $status
     * @return bool
     */
    public function inflictStatus(Citizen $citizen, CitizenStatus|string $status ): bool {
        if (is_string( $status )) $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName($status);
        if (!$status) return false;

        if ( $this->hasStatusEffect($citizen, 'tg_stats_locked') ) return false;

        if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] )) {
            $this->inflictWound($citizen);
            return true;
        }

        // Prevent thirst and infection for ghouls; ghoul infection is still possible
        if ((   $status->getName() === 'thirst1' ||
                $status->getName() === 'thirst2' ||
                $status->getName() === 'infection' ||
                $status->getName() === 'tg_meta_winfect'
            ) && $citizen->hasRole('ghoul'))
            return false;

        // Convert ghoul infection into normal infection
        if ( $status->getName() === 'tg_meta_ginfect')
            $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('infection');

        // Prevent a normal infection when immune
        if ( $status->getName() === 'infection' && $this->hasStatusEffect( $citizen, 'immune' ) )
            return false;

        // Convert wound infection into normal infection
        if ( $status->getName() === 'tg_meta_winfect')
            $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('infection');

        // Prevent terror when holding a zen booklet
        if ($status->getName() === 'terror' && $this->inventory_handler->countSpecificItems(
                $citizen->getInventory(),
                $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('lilboo_#00') )
        ) return $this->hasStatusEffect( $citizen, 'terror' );

        if (in_array($status->getName(), ['drugged','addict']))
            $this->removeStatus($citizen, 'clean');

        $citizen->addStatus( $status );
        return true;
    }

    public function removeStatus( Citizen &$citizen, $status ): bool {
        if (is_string( $status )) $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName($status);
        if (!$status) return false;

        if ( $this->hasStatusEffect($citizen, 'tg_stats_locked') && $status->getName() !== 'tg_stats_locked' )
            return false;

        if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] )) {
            $this->healWound($citizen);
            return true;
        }

        $citizen->removeStatus( $status );
        return true;
    }

    public function increaseThirstLevel( Citizen $citizen ) {

        if ($citizen->hasRole('ghoul')) return;

        $lv2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'thirst2']);
        $lv1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'thirst1']);

        if ($citizen->getStatus()->contains( $lv2 )) {
            $this->container->get(DeathHandler::class)->kill($citizen, CauseOfDeath::Dehydration);
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
        } elseif ($citizen->getStatus()->contains( $lv1 )) {
            $this->removeStatus( $citizen, $lv1 );
            $this->inflictStatus( $citizen, $lv2 );
        } else $this->inflictStatus( $citizen, $lv1 );

    }

    public function updateBanishment( Citizen &$citizen, ?Building $gallows, ?Building $cage, ?Building &$active = null, bool $forceBan = false ): bool {

        $active = null;
        if (!$citizen->getAlive() || $citizen->getTown()->getChaos()) return false;

        $action = false; $kill = false;

        $nbComplaint = $this->entity_manager->getRepository(Complaint::class)->countComplaintsFor($citizen, Complaint::SeverityBanish);

        $conf = $this->conf->getTownConfiguration( $citizen->getTown() );
        $complaintNeeded = $conf->get(TownConf::CONF_MODIFIER_COMPLAINTS_SHUN, 8);
        $complaintNeededKill = $conf->get(TownConf::CONF_MODIFIER_COMPLAINTS_KILL, 6);
        $shunningEnabled = $conf->get(TownConf::CONF_FEATURE_SHUN, true);

        // If the citizen is already shunned and cage/gallows is not built, do nothing
        if ($citizen->getBanished()) {
            if (!$gallows && !$cage) return false;
            $complaintNeeded = $complaintNeededKill;
        }

        if (($shunningEnabled || $gallows || $cage) && ($nbComplaint >= $complaintNeeded || $forceBan))
            $action = true;

        if ($action && ($gallows || $cage) && !$forceBan)
            $kill = true;

        if ($action) {
            if (!$citizen->getBanished() && !$kill) {
                $this->entity_manager->persist($this->log->citizenBanish($citizen));
                $citizen->setBanished(true);
            }

            if ($citizen->hasRole('cata'))
                $citizen->removeRole($this->entity_manager->getRepository(CitizenRole::class)->findOneBy(['name' => 'cata']));

            // Disable escort on banishment
            if ($citizen->getEscortSettings()) {
                $this->entity_manager->remove($citizen->getEscortSettings());
                $citizen->setEscortSettings(null);
            }

            if (!$kill) {
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_ban_#00' ]);
                $this->picto_handler->give_picto($citizen, $pictoPrototype);
            }

            $itemsForLog = $this->recoverBanItems($citizen, $kill);
            if (!$kill) {
                $itemlist = [];
                foreach ($itemsForLog as $id => ['count' => $count]) for ($i = 0; $i < $count; $i++)
                    $itemlist[] = $id;
                $this->crow->postAsPM( $citizen, '', '', PrivateMessage::TEMPLATE_CROW_BANISHMENT, null, $itemlist );
            }

            // As he is shunned, we remove all the complaints
            $complaints = $this->entity_manager->getRepository(Complaint::class)->findByCulprit($citizen);
            foreach ($complaints as $complaint) {
                $this->entity_manager->remove($complaint);
            }
        }

        if ($kill) {
            $rem = [];
            // The gallow is used before the cage
            // Since the gallow building can also be a chocolate cross, we need to check the type
            if ($gallows && $gallows->getPrototype()->getName() === 'small_eastercross_#00') {
                $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::ChocolateCross, $rem );
                // TODO: Add the log entry template

                // The chocolate cross gets destroyed
                $gallows->setComplete(false)->setAp(0)->setDefense(0)->setHp(0);
                $this->gps->recordBuildingCollapsed( $gallows->getPrototype(), $citizen->getTown() );
                $active = $gallows;
            } elseif ($gallows) {
                $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::Hanging, $rem );
                $this->entity_manager->persist($this->log->publicJustice($citizen));

                // The gallows gets destroyed
                $gallows->setComplete(false)->setAp(0)->setDefense(0)->setHp(0);
                $this->gps->recordBuildingCollapsed( $gallows->getPrototype(), $citizen->getTown() );
                $active = $gallows;
            } elseif ($cage) {
                $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::FleshCage, $rem );
                $cage->setTempDefenseBonus( $cage->getTempDefenseBonus() + ($def = $citizen->getProfession()->getHeroic() ? 60 : 40 ) );
                $this->entity_manager->persist($this->log->publicJustice($citizen, $def));

                $this->entity_manager->persist( $cage );
                $citizen->getHome()->setHoldsBody(false);
                $active = $cage;
            }
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, null ) );
            foreach ($rem as $r) $this->entity_manager->remove( $r );

        } else if ($action && $citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'revenge') && $citizen->getTown()->getDay() >= 3) {
            $this->inventory_handler->forceMoveItem( $citizen->getInventory(), $this->item_factory->createItem( 'poison_#00' ));
            $this->inventory_handler->forceMoveItem( $citizen->getInventory(), $this->item_factory->createItem( 'poison_#00' ));
        }

        if (!empty($itemsForLog))
            $this->entity_manager->persist(
                $this->log->bankBanRecovery($citizen, $itemsForLog, $active !== null && $active === $gallows, $active !== null && $active === $cage)
            );

        return $action;
    }

    private function recoverBanItems(Citizen $citizen, bool $kill): array {
        /** @var Item[] $items */
        $items = [];
        $impound_prop = $this->entity_manager->getRepository(ItemProperty::class)->findOneBy(['name' => 'impoundable' ]);
        if ($citizen->getZone() === null) // Only citizen banned in town gets their rucksack emptied
            foreach ( $citizen->getInventory()->getItems() as $item )
                if (!$item->getEssential() && ($kill || $item->getPrototype()->getProperties()->contains( $impound_prop )))
                    $items[] = $item;

        foreach ( $citizen->getHome()->getChest()->getItems() as $item )
            if (!$item->getEssential() && ($kill || $item->getPrototype()->getProperties()->contains( $impound_prop )))
                $items[] = $item;

        $bank = $citizen->getTown()->getBank();
        foreach ($items as $item) {
            $this->inventory_handler->forceMoveItem( $bank, $item );
        }

        $itemsForLog = [];
        foreach ($items as $item){
            if(isset($itemsForLog[$item->getPrototype()->getId()])) {
                $itemsForLog[$item->getPrototype()->getId()]['count']++;
            } else {
                $itemsForLog[$item->getPrototype()->getId()] = [
                    'item' => $item->getPrototype(),
                    'count' => 1
                ];
            }
        }

        return $itemsForLog;
    }

    public function pass_airborne_ghoul_infection(?Citizen $citizen, ?Town $town = null) {
        $cc = [];
        foreach (($citizen ? $citizen->getTown() : $town)->getCitizens() as $c)
            if ($c !== $citizen && $c->getAlive() && !$this->hasRole($c, 'ghoul') && !$this->hasStatusEffect($c, 'tg_air_infected'))
                $cc[] = $c;

        if (!empty($cc)) $c = $this->random_generator->pick($cc);
        $this->inflictStatus($c, 'tg_air_infected');
        $this->entity_manager->persist($c);
    }

    /**
     * @param Citizen $citizen
     * @param CitizenRole|string $role
     * @return bool
     */
    public function addRole(Citizen $citizen, $role): bool {

        if (is_string($role)) $role = $this->entity_manager->getRepository(CitizenRole::class)->findOneByName($role);
        /** @var $role CitizenRole|null */
        if (!$role) return false;

        if (!$citizen->getRoles()->contains($role)) {

            if ($role->getName() === 'ghoul' && ($this->hasStatusEffect($citizen, 'immune') || $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GHOUL_MODE, 'normal') === 'childtown'))
                return false;

            $citizen->addRole($role);

            switch($role->getName()){
                case "ghoul":
                    $this->removeStatus($citizen, 'thirst1');
                    $this->removeStatus($citizen, 'thirst2');
                    $this->removeStatus($citizen, 'infection');
                    $this->removeStatus($citizen, 'tg_meta_wound');
                    $this->removeStatus($citizen, 'tg_meta_winfect');
                    $citizen->setWalkingDistance(0);

                    // If the citizen is marked to become a ghoul after the next attack, pass the mark on to another
                    // citizen
                    if ($this->hasStatusEffect($citizen, 'tg_air_infected')) {
                        $this->pass_airborne_ghoul_infection($citizen);
                        $this->removeStatus($citizen, 'tg_air_infected');
                    }

                    break;
                case "shaman":
                    $this->inflictStatus($citizen, "tg_shaman_immune"); // Shaman is immune to red souls
                    $this->setPM($citizen, false, $this->getMaxPM($citizen)); // We give him his PM
                    break;
            }

            return true;

        } else return true;
    }

    /**
     * @param Citizen $citizen
     * @param CitizenRole|string $role
     * @return bool
     */
    public function removeRole(Citizen $citizen, $role): bool {

        if (is_string($role)) $role = $this->entity_manager->getRepository(CitizenRole::class)->findOneByName($role);
        /** @var $role CitizenRole|null */
        if (!$role) return false;

        if ($citizen->getRoles()->contains($role)) {
            $citizen->removeRole($role);
            switch($role->getName()){
                case "ghoul":
                    $citizen->setWalkingDistance(0);
                    $this->removeStatus($citizen, 'tg_air_ghoul');
                    break;
                case "shaman":
                    $this->removeStatus($citizen, 'tg_shaman_immune');
                    $this->setPM($citizen, false, 0); // We remove him his PM
                    break;
            }
            return true;
        } else return true;
    }

    public function isTired(Citizen $citizen) {
        foreach ($citizen->getStatus() as $status)
            if ($status->getName() === 'tired') return true;
        return false;
    }

    public function getMaxAP(Citizen $citizen) {
        return $this->isWounded($citizen) ? 5 : 6;
    }

    /**
     * Set the AP of a citizen.
     * @param Citizen &$citizen The citizen on which we'll change AP
     * @param bool $relative Is this set relative to current citizen AP or not?
     * @param int $num The number of AP to set
     * @param int $max_bonus The bonus to apply to max AP (default null)
     * @return int The number of affected AP to citizen (may be different from what was asked because of some rules)
     */
    public function setAP(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null): int {
        $beforeAp = $citizen->getAp();
        
        if ($max_bonus !== null) {
            $citizen->setAp( max(0, min(max($this->getMaxAP( $citizen ) + $max_bonus, $citizen->getAp()), $relative ? ($citizen->getAp() + $num) : max(0,$num) )) );
        } else {
            $citizen->setAp( max(0, $relative ? ($citizen->getAp() + $num) : max(0,$num) ) );
        }
        
        $citizen->getAp() == 0 ? $this->inflictStatus( $citizen, 'tired' ) : $this->removeStatus( $citizen, 'tired' );

        return $citizen->getAp() - $beforeAp;
    }

    public function getMaxBP(Citizen $citizen) {
        return $citizen->getProfession()->getName() === 'tech' ? 6 : 0;
    }

    public function setBP(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null) {
        if ($max_bonus !== null)
            $citizen->setBp( max(0, min(max($this->getMaxBP( $citizen ) + $max_bonus, $citizen->getBp()), $relative ? ($citizen->getBp() + $num) : max(0,$num) )) );
        else $citizen->setBp( max(0, $relative ? ($citizen->getBp() + $num) : max(0,$num) ) );
    }

    /**
     * Returns the maximum PM available for a citizen
     * @param Citizen $citizen The citizen to look for
     * @return int Number of maximum PM available for the citizen
     */
    public function getMaxPM(Citizen $citizen) {
        $isShaman = false;
        foreach ($citizen->getRoles() as $role) {
            if($role->getName() == "shaman")
                $isShaman = true;
        }
        return $isShaman ? 5 : 0;
    }

    public function setPM(Citizen &$citizen, bool $relative, int $num) {
        $citizen->setPm(max(0, $relative ? ($citizen->getPm() + $num) : max(0,$num) ) );
    }

    public function deductAPBP(Citizen &$citizen, int $ap, ?int &$usedap = 0, ?int &$usedbp = 0) {
        if ($ap <= $citizen->getBp()) {
            $usedbp = $ap;
            $this->setBP($citizen, true, -$ap);
        } else {
            $ap -= $citizen->getBp();
            $usedbp = $citizen->getBp();
            $usedap = $ap;
            $this->setAP($citizen, true, -$ap);
            $this->setBP($citizen, false, 0);
        }
    }

    public function getCP(Citizen &$citizen): int {
        if ($this->hasStatusEffect( $citizen, 'terror', false )) $base = 0;
        else {
            $base = $citizen->getProfession()->getName() == 'guardian' ? 4 : 2;

            $has_healthy_body = $citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'healthybody');
            $has_body_armor   = $citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'brick');

            if ($has_healthy_body && $this->hasStatusEffect( $citizen, 'clean', false ))
                $base += 1;

            if ($has_body_armor)
                $base += 1;

            if (!empty($this->inventory_handler->fetchSpecificItems(
                $citizen->getInventory(), [new ItemRequest( 'car_door_#00' )]
            ))) $base += 1;

            if ($citizen->hasRole('guide'))
                $base += $citizen->getZone() ? $citizen->getZone()->getCitizens()->count() : 0;
        }

        return $base;
    }

    public function applyProfession(Citizen &$citizen, CitizenProfession &$profession): void {
        $item_type_cache = [];

        if ($citizen->getProfession() === $profession) return;

        if ($citizen->getProfession()) {
            foreach ($citizen->getProfession()->getProfessionItems() as $pi)
                if (!isset($item_type_cache[$pi->getId()])) $item_type_cache[$pi->getId()] = [-1,$pi];
            foreach ($citizen->getProfession()->getAltProfessionItems() as $pi)
                if (!isset($item_type_cache[$pi->getId()])) $item_type_cache[$pi->getId()] = [-1,$pi];
        }

        foreach ($profession->getProfessionItems() as $pi)
            if (!isset($item_type_cache[$pi->getId()])) $item_type_cache[$pi->getId()] = [1,$pi];
            else $item_type_cache[$pi->getId()] = [0,$pi];

        $inventory = $citizen->getInventory(); $null = null;
        foreach ($item_type_cache as &$entry) {
            list($action,$proto) = $entry;

            if ($action < 0) foreach ($this->inventory_handler->fetchSpecificItems( $inventory, [new ItemRequest($proto->getName(),1,null,null)] ) as $item)
                $this->inventory_handler->transferItem($citizen,$item,$inventory,$null);
            if ($action > 0) {
                $item = $this->item_factory->createItem( $proto );
                $item->setEssential(true);
                $this->inventory_handler->transferItem($citizen,$item,$null,$inventory);
            }
        }

        $prev = $citizen->getProfession();
        $citizen->setProfession( $profession );

        if (!$prev || $prev->getName() === 'none')
            $this->gps->recordCitizenProfessionSelected( $citizen );
        else $this->gps->recordCitizenCitizenProfessionChanged( $citizen, $prev );

        if ($profession->getName() === 'tech')
            $this->setBP($citizen,false, $this->getMaxBP( $citizen ),0);
        else $this->setBP($citizen, false, 0);

        $this->setPM($citizen, false, 0);

        if ($profession->getName() !== 'none')
            $this->entity_manager->persist( $this->log->citizenJoinProfession( $citizen ) );

    }

    /**
     * Tries to apply an alias to the citizen.
     * 
     * @param Citizen $citizen
     * @param string $alias
     * @return int 1: Alias applied, 0: Alias not applied, -1: Error to report
     */
    public function applyAlias(Citizen &$citizen, string $alias) {
        if (!empty($alias) && $alias !== $citizen->getUser()->getName()) {


            if (!$this->user_handler->isNameValid( $alias ))
                return -1;

            if (mb_strlen($alias) < 4 || mb_strlen($alias) > 22 || preg_match('/[^\w]/', $alias))
                return -1;

            $citizen->setAlias( $alias ); // nbsp

            return 1;
        }
        return 0;
    }

    public function getSoulpoints(Citizen $citizen): int {
        $days = $citizen->getSurvivedDays();
        return $days * ( $days + 1 ) / 2;
    }

    public function getCampingChance(Citizen $citizen): float {
        // In order to don't overflow 100%, we take the min between 0 and the camping value.
        // Camping value is going more and more negative when your camping chances are dropping.
        // The survivalist job can reach 100% of camping survival chances. Others are stuck at 90%.
        // We found out that there seems to be a minimum of survival chances of 10%. We should confirm
        // this with more tests on the original game. But for now, let's take 10%.
        return min(max((100.0 - (abs(min(0, array_sum($this->getCampingValues($citizen)))) * 5)) / 100.0, .1), $citizen->getProfession()->getName() === 'survivalist' ? 1.0 : 0.9);
    }

    public function getCampingValues(Citizen $citizen): array {
        // Based on https://docs.google.com/spreadsheets/d/1uxSAGoNUIhSPGY7fj_3yPzJEri9ktEXLj9Wt7x_B9Ig/edit#gid=555313428
        // and on   http://www.camping-predict.nadazone.fr/
        $camping_values = [];
        $zone = $citizen->getZone();
        $town = $citizen->getTown();
        $has_pro_camper = $citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'procamp');
        $has_scout_protection = $this->inventory_handler->countSpecificItems(
                $citizen->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
            ) > 0;
        $is_panda = $town->getType()->getName() === 'panda';

        $config = $this->conf->getTownConfiguration($citizen->getTown());

        // Town type: Pandemonium gets malus of 14, all other types are neutral.
        $camping_values['town'] = (int)$config->get(TownConf::CONF_MODIFIER_CAMPING_BONUS, 0);

        // Distance in km
        $distance_map = [
            1 => -24,
            2 => -19,
            3 => -14,
            4 => -11,
            5 => -9,
            6 => -9,
            7 => -9,
            8 => -9,
            9 => -9,
            10 => -9,
            11 => -9,
            12 => -8,
            13 => -7.6,
            14 => -7,
            15 => -6,
        ];

        $zone_distance = $zone->getDistance();
        if ($zone_distance >= 16) {
            $camping_values['distance'] = -5;
        }
        else {
            $camping_values['distance'] = $distance_map[$zone_distance];
        }

        // Ruin in zone.
        $camping_values['ruin'] = $zone->getPrototype() ? ($zone->getBuryCount() <= 0 ? $zone->getPrototype()->getCampingLevel() : 8) : 0;

        // Zombies in zone. Factor -1.4, for hidden scouts it is -0.6.
        $camping_values['zombies'] = ($has_scout_protection ? -0.6 : -1.4) * $zone->getZombies();

        // Zone improvement level.
        $camping_values['improvement'] = $zone->getImprovementLevel();

        // Camping count. After each night in camping, you have an increasing malus.
        // Arbitrary values are here to be sure that citizen will be camping with lowest chance (or make the night count in array).
        $campings_map = [
            'normal' => [
                'nonpro' => [
                    0 => 0,
                    1 => -4,
                    2 => -9,
                    3 => -13,
                    4 => -16,
                    5 => -26,
                    6 => -36,
                    7 => -50, // Totally arbitrary
                    8 => -65, // Totally arbitrary
                    9 => -80 // Totally arbitrary
                ],
                'pro' => [
                    0 => 0,
                    1 => -2,
                    2 => -4,
                    3 => -8,
                    4 => -10,
                    5 => -12,
                    6 => -16,
                    7 => -26,
                    8 => -36,
                    9 => -60 // Totally arbitrary
                ]
            ],
            'hard' => [
                'nonpro' => [
                    0 => 0,
                    1 => -4,
                    2 => -6,
                    3 => -8,
                    4 => -10,
                    5 => -20,
                    6 => -36,
                    7 => -50, // Totally arbitrary
                    8 => -65, // Totally arbitrary
                    9 => -80 // Totally arbitrary
                ],
                'pro' => [
                    0 => 0,
                    1 => -1,
                    2 => -2,
                    3 => -4,
                    4 => -6,
                    5 => -8,
                    6 => -10,
                    7 => -20,
                    8 => -36, // Totally arbitrary
                    9 => -60 // Totally arbitrary
                ]
            ],
        ];

        $camping_values['campings'] = $campings_map[$config->get(TownConf::CONF_MODIFIER_CAMPING_CHANCE_MAP, 'normal')][$has_pro_camper ? 'pro' : 'nonpro'][min(9,$citizen->getCampingCounter())];

        // Campers that are already hidden.
        $campers_map = [
            0 => 0,
            1 => 0,
            2 => -2,
            3 => -6,
            4 => -10,
            5 => -14,
            6 => -20
        ];

        $previous_campers = 0;
        $zone_campers = $zone->getCampers();
        foreach ($zone_campers as $camper) {
            if ($camper !== $citizen) {
                $previous_campers++;
            }
            else {
                break;
            }
        }
        if ($previous_campers >= 7) {
            $camping_values['campers'] = -26;
        }
        else {
            $camping_values['campers'] = $campers_map[$previous_campers];
        }

        // Hautfetzen + Zeltplanen
        $campitems = [
            $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'smelly_meat_#00' ),
            $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'sheet_#00' ),
        ];
        $camping_values['campitems'] = $this->inventory_handler->countSpecificItems($citizen->getInventory(), $campitems, false, false);

        // Grab
        $camping_values['tomb'] = 0;
        if ($citizen->getStatus()->contains( $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' ) )) {
            $camping_values['tomb'] = 1.6;
        }

        // Night time bonus.
        $camping_values['night'] = 0;
        $camping_datetime = new DateTime();
        if ($citizen->getCampingTimestamp() > 0)
            $camping_datetime->setTimestamp( $citizen->getCampingTimestamp() );
        if ($config->isNightMode())
            $camping_values['night'] = 2;

        // Leuchtturm
        $camping_values['lighthouse'] = 0;

        if ($this->container->get(TownHandler::class)->getBuilding( $town, "small_lighthouse_#00", true ))
            $camping_values['lighthouse'] = 5; //camping improvement or percent ? Because it's 5 camping improvement normally

        // Devastated town.
        $camping_values['devastated'] = $town->getDevastated() ? -10 : 0;

        return $camping_values;
    }

    public function getNightwatchProfessionDefenseBonus(Citizen $citizen): int{
        /*if ($citizen->getProfession()->getName() == "guardian") {
            return 30;
        } else if ($citizen->getProfession()->getName() == "tamer") {
            return 20;
        }*/

        return $citizen->getProfession()->getNightwatchDefenseBonus();
    }

    public function getNightwatchProfessionSurvivalBonus(Citizen $citizen){
        /*if ($citizen->getProfession()->getName() == "guardian") {
            return 0.04;
        }*/
        return $citizen->getProfession()->getNightwatchSurvivalBonus();
    }

    public function getNightwatchBaseFatigue(Citizen $citizen): float{
        if ($citizen->getProfession()->getName() == "guardian") {
            return 0.01;
        }
        return 0.05;
    }

    public function getDeathChances(Citizen $citizen, bool $during_attack = false): float {
        $fatigue = $this->getNightwatchBaseFatigue($citizen);

        $is_pro = ($citizen->getProfession()->getHeroic() && $this->user_handler->hasSkill($citizen->getUser(), 'prowatch'));

        for($i = 1 ; $i <= $citizen->getTown()->getDay() - ($during_attack ? 2 : 1); $i++){
            /** @var CitizenWatch|null $previousWatches */
            $previousWatches = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchOfCitizenForADay($citizen, $i);
            if ($previousWatches === null || $previousWatches->getSkipped())
                $fatigue = max($this->getNightwatchBaseFatigue($citizen), $fatigue - ($is_pro ? 0.025 : 0.05));
            else
                $fatigue += ($is_pro ? 0.05 : 0.1);
        }

        $chances = max($this->getNightwatchBaseFatigue($citizen), $fatigue);
        foreach ($citizen->getStatus() as $status)
            $chances += $status->getNightWatchDeathChancePenalty();
        if($citizen->hasRole('ghoul')) $chances -= 0.05;

        return round($chances, 2, PHP_ROUND_HALF_DOWN);
    }

    public function getNightWatchItemDefense( Item $item, bool $shooting_gallery, bool $trebuchet, bool $ikea, bool $armory ): int {
        if ($item->getBroken()) return 0;

        $bonus = [];
        if ($shooting_gallery && $item->getPrototype()->hasProperty('nw_shooting'))  $bonus[] = 0.2;
        if ($trebuchet        && $item->getPrototype()->hasProperty('nw_trebuchet')) $bonus[] = 0.2;
        if ($ikea             && $item->getPrototype()->hasProperty('nw_ikea'))      $bonus[] = 0.2;
        if ($armory           && $item->getPrototype()->hasProperty('nw_armory'))    $bonus[] = 0.2;

        $total = $item->getPrototype()->getWatchpoint();
        foreach ($bonus as $single)
            $total = (int)floor( $total * (1.0+$single) );

        return $total;
    }

    public function getNightWatchDefense(Citizen $citizen, bool $shooting_gallery, bool $trebuchet, bool $ikea, bool $armory): int {
        $def = 10 + $this->getNightwatchProfessionDefenseBonus($citizen);

        foreach ($citizen->getStatus() as $status)
            $def += $status->getNightWatchDefenseBonus();

        foreach ($citizen->getInventory()->getItems() as $item)
            $def += $this->getNightWatchItemDefense($item, $shooting_gallery, $trebuchet, $ikea, $armory);

        return $def;
    }

    /**
     * @param Citizen $citizen
     * @param string|CitizenRole|string[]|CitizenRole[] $role
     * @param bool $all
     * @return bool
     */
    public function hasRole( Citizen $citizen, $role, bool $all = false ): bool {
        $role = array_map(function($r): string {
            /** @var $r string|CitizenRole */
            if (is_a($r, CitizenRole::class)) return $r->getName();
            elseif (is_string($r)) return $r;
            else return '???';
        }, is_array($role) ? $role : [$role]);

        if ($all) {
            foreach ($citizen->getRoles() as $r)
                if (!in_array($r->getName(), $role)) return false;
        } else {
            foreach ($citizen->getRoles() as $r)
                if (in_array($r->getName(), $role)) return true;
        }
        return $all;
    }

    public function houseIsProtected(Citizen $c, bool $only_explicit_lock = false) {
        if (!$c->getAlive()) return false;
        if (!$c->getZone() && !$only_explicit_lock) return true;
        if ($c->getHome()->getPrototype()->getTheftProtection()) return true;
        if ($this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
            $c->getHome(),
            $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( 'lock' ) ))
            return true;
        if ($this->inventory_handler->countSpecificItems( $c->getHome()->getChest(), 'lock', true, false ) > 0)
            return true;
        return false;
    }

    public function hasNewMessage(Citizen $c){
        $threads = $this->entity_manager->getRepository(PrivateMessageThread::class)->findNonArchived($c);
        foreach ($threads as $thread) {
            if($thread->getArchived()) continue;
            foreach ($thread->getMessages() as $message) {
                if($message->getRecipient() == $c && $message->getNew())
                    return true;
            }
        }

        return false;
    }

    public function getActivityLevel(Citizen $citizen): int {
        $level = 0;
        if($this->hasStatusEffect($citizen, 'tg_chk_forum')) $level++;
        if($this->hasStatusEffect($citizen, 'tg_chk_active')) $level++;
        if($this->hasStatusEffect($citizen, 'tg_chk_workshop')) $level++;
        if($this->hasStatusEffect($citizen, 'tg_chk_build')) $level++;
        if($this->hasStatusEffect($citizen, 'tg_chk_movewb')) $level++;
        return $level;
    }

    public function getDecoPoints(Citizen $citizen, &$decoItems = []): int {
        $deco = 0;
        foreach ($citizen->getHome()->getChest()->getItems() as $item) {
            /** @var Item $item */
            if ($item->getBroken()) continue;
            $deco += $item->getPrototype()->getDeco();
            if ($item->getPrototype()->getDeco() || !empty($item->getPrototype()->getDecoText()))
                $decoItems[] = $item;
        }

        return $deco;
    }
}