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
use App\Entity\Zone;
use App\Enum\ActionHandler\PointType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\EventStages\CitizenValueQuery;
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
    private EventProxyService $events;

    public function __construct(EntityManagerInterface $em, RandomGenerator $g, InventoryHandler $ih,
                                PictoHandler $ph, ItemFactory $if, LogTemplateHandler $lh, ContainerInterface $c, UserHandler $uh,
                                ConfMaster $conf, GameProfilerService $gps, CrowService $crow, EventProxyService $events )
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
        $this->events = $events;
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

    public function inflictWound( Citizen $citizen ): ?CitizenStatus {
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
     * @param bool $force
     * @return bool
     */
    public function inflictStatus(Citizen $citizen, CitizenStatus|string $status, bool $force = false ): bool {
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
        if ( !$force && $status->getName() === 'infection' && $this->hasStatusEffect( $citizen, 'immune' ) )
            return false;

        // Convert wound infection into normal infection
        if ( $status->getName() === 'tg_meta_winfect')
            $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('infection');

        // Prevent terror when holding a zen booklet
        if ( !$force && $status->getName() === 'terror' && $this->inventory_handler->countSpecificItems(
                $citizen->getInventory(),
                $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('lilboo_#00') )
        ) return $this->hasStatusEffect( $citizen, 'terror' );

        if (in_array($status->getName(), ['drugged','addict']))
            $this->removeStatus($citizen, 'clean');

        $citizen->addStatus( $status );
        return true;
    }

    public function removeStatus( Citizen $citizen, $status ): bool {
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
            foreach ($citizen->getLeadingEscorts() as $escort)
                $this->entity_manager->persist( $escort->getCitizen()->getEscortSettings()->setLeader(null) );
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

        } else if ($citizen->getTown()->getDay() >= 3)
            foreach ($citizen->property( CitizenProperties::RevengeItems ) as $item)
                $this->inventory_handler->forceMoveItem( $citizen->getInventory(), $this->item_factory->createItem( $item ));

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

		if (empty($cc)) return; // no citizen to infect, we leave

        $c = $this->random_generator->pick($cc);
        $this->inflictStatus($c, 'tg_air_infected');
        $this->entity_manager->persist($c);
    }

    /**
     * @param Citizen $citizen
     * @param CitizenRole|string $role
     * @return bool
     */
    public function addRole(Citizen $citizen, CitizenRole|string $role): bool {

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

    public function getMaxAP(Citizen $citizen, bool $includeBase = true): int
    {
        if (!$includeBase) return 0;
        return $this->isWounded($citizen) ? 5 : 6;
    }

    /**
     * Set the AP of a citizen.
     * @param Citizen $citizen The citizen on which we'll change AP
     * @param bool $relative Is this set relative to current citizen AP or not?
     * @param int $num The number of AP to set
     * @param ?int $max_bonus The bonus to apply to max AP (default null)
     * @return int The number of affected AP to citizen (may be different from what was asked because of some rules)
     */
    public function setAP(Citizen $citizen, bool $relative, int $num, ?int $max_bonus = null): int {
        $beforeAp = $citizen->getAp();
        
        if ($max_bonus !== null) {
            $citizen->setAp( max(0, min(max($this->getMaxAP( $citizen ) + $max_bonus, $citizen->getAp()), $relative ? ($citizen->getAp() + $num) : max(0,$num) )) );
        } else {
            $citizen->setAp( max(0, $relative ? ($citizen->getAp() + $num) : max(0,$num) ) );
        }
        
        $citizen->getAp() == 0 ? $this->inflictStatus( $citizen, 'tired' ) : $this->removeStatus( $citizen, 'tired' );

        return $citizen->getAp() - $beforeAp;
    }

    /**
     * Returns the max construction points for a citizen
     * @param Citizen $citizen
     * @param bool $includeBase True to include base CP (default)
     * @return int
     */
    public function getMaxBP(Citizen $citizen, bool $includeBase = true): int {
        if (!$includeBase) return 0;
        return $citizen->getProfession()->getName() === 'tech' ? 6 : 0;
    }

    public function setBP(Citizen $citizen, bool $relative, int $num, ?int $max_bonus = null): void {
        if ($max_bonus !== null)
            $citizen->setBp( max(0, min(max($this->getMaxBP( $citizen ) + $max_bonus, $citizen->getBp()), $relative ? ($citizen->getBp() + $num) : max(0,$num) )) );
        else $citizen->setBp( max(0, $relative ? ($citizen->getBp() + $num) : max(0,$num) ) );
    }

    /**
     * Returns the maximum PM available for a citizen
     * @param Citizen $citizen The citizen to look for
     * @param bool $includeBase True to include base MP (default)
     * @return int Number of maximum PM available for the citizen
     */
    public function getMaxPM(Citizen $citizen, bool $includeBase = true): int {
        $isShaman = false;
        if (!$includeBase) return 0;
        foreach ($citizen->getRoles() as $role) {
            if($role->getName() == "shaman")
                $isShaman = true;
        }
        return $isShaman ? 5 : 0;
    }

    /**
     * Returns the maximum SP available for a citizen
     * @param Citizen $citizen The citizen to look for
     * @param bool $includeBase True to include base SP (default)
     * @return int Number of maximum SP available for the citizen
     */
    public function getMaxSP(Citizen $citizen, bool $includeBase = true): int {
        return (($includeBase && $citizen->getProfession()->getName() === 'hunter') ? 2 : 0) +
            $this->events->queryCitizenParameter( $citizen, CitizenValueQuery::MaxSpExtension );
    }

    public function setPM(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null): void {
        if ($max_bonus !== null)
            $citizen->setPm( max(0, min(max($this->getMaxPM( $citizen ) + $max_bonus, $citizen->getPm()), $relative ? ($citizen->getPm() + $num) : max(0,$num) )) );
        else $citizen->setPm(max(0, $relative ? ($citizen->getPm() + $num) : max(0,$num) ) );
    }

    public function setSP(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null): void {
        if ($max_bonus !== null)
            $citizen->setSp( max(0, min(max($this->getMaxSP( $citizen ) + $max_bonus, $citizen->getSp()), $relative ? ($citizen->getSp() + $num) : max(0,$num) )) );
        else $citizen->setSp(max(0, $relative ? ($citizen->getSp() + $num) : max(0,$num) ) );
    }

    public function checkPointsWithFallback(Citizen $citizen, PointType $fallback, PointType $primary, int $points): bool {
        return $fallback === $primary
            ? ($citizen->getPoints($primary) >= $points)
            : (($citizen->getPoints($primary) + $citizen->getPoints($fallback)) >= $points);
    }

    public function deductPointsWithFallback(Citizen $citizen, PointType $fallback, PointType $primary, int $points, ?int &$usedFallback = 0, ?int &$usedPrimary = 0): void
    {
        if ($points <= $citizen->getPoints($primary)) {
            $usedPrimary = $points;
            $this->setPoints($citizen, $primary, true, -$points);
        } elseif ($primary === $fallback) {
            $usedPrimary = $citizen->getPoints($primary);
            $this->setPoints($citizen, $primary, false, 0);
        } else {
            $points -= $citizen->getPoints($primary);
            $usedPrimary = $citizen->getPoints($primary);
            $usedFallback = $points;
            $this->setPoints($citizen, $fallback, true, -$points);
            $this->setPoints($citizen, $primary, false, 0);
        }
    }

    public function getCP(Citizen $citizen): int {
        if ($this->hasStatusEffect( $citizen, 'terror', false )) $base = 0;
        else {
            $base = ($citizen->getProfession()->getName() == 'guardian' ? 4 : 2) +
                $citizen->property( CitizenProperties::ZoneControlBonus );

            if ($citizen->hasStatus('clean'))
                $base += $citizen->property( CitizenProperties::ZoneControlCleanBonus );

            if (!empty($this->inventory_handler->fetchSpecificItems(
                $citizen->getInventory(), [new ItemRequest( 'car_door_#00' )]
            ))) $base += 1;

            if ($citizen->hasRole('guide'))
                $base += $citizen->getZone() ? $citizen->getZone()->getCitizens()->count() : 0;
        }

        return $base;
    }

    public function getMaxPoints(Citizen $citizen, PointType $t, bool $includeBase = true): int {
        return match ($t) {
            PointType::AP => $this->getMaxAP($citizen, $includeBase),
            PointType::CP => $this->getMaxBP($citizen, $includeBase),
            PointType::MP => $this->getMaxPM($citizen, $includeBase),
            PointType::SP => $this->getMaxSP($citizen, $includeBase),
        };
    }

    public function setPoints(Citizen $citizen, PointType $t, bool $relative, int $num, ?int $max_bonus = null): void {
        switch ($t) {
            case PointType::AP:
                $this->setAP( $citizen, $relative, $num, $max_bonus );
                break;
            case PointType::CP:
                $this->setBP( $citizen, $relative, $num, $max_bonus);
                break;
            case PointType::MP:
                $this->setPM( $citizen, $relative, $num, $max_bonus);
                break;
            case PointType::SP:
                $this->setSP( $citizen, $relative, $num, $max_bonus);
                break;
        }
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
                $this->events->transferItem($citizen,$item,$inventory);
            if ($action > 0) {
                $item = $this->item_factory->createItem( $proto );
                $item->setEssential(true);
                $this->events->transferItem($citizen, $item, to: $inventory);
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

        if ($profession->getName() === 'hunter')
            $this->setSP($citizen,false, $this->getMaxSP( $citizen ),0);
        else $this->setSP($citizen, false, 0);

        $this->setPM($citizen, false, 0);

        if ($profession->getName() !== 'none')
            $this->entity_manager->persist( $this->log->citizenJoinProfession( $citizen ) );

        if (!$prev?->getHeroic() && $profession->getHeroic())
            foreach ($citizen->getSpecialActions() as $specialAction)
                if ($specialAction->getProxyFor())
                    $citizen->removeSpecialAction( $specialAction );

    }

    public function getSoulpoints(Citizen $citizen): int {
        $days = $citizen->getSurvivedDays();
        return $days * ( $days + 1 ) / 2;
    }

    public function getCampingOdds(Citizen $citizen): float {
		return max(0, min(array_sum($this->getCampingValues($citizen)) / 100.0, $citizen->getProfession()->getName() === 'survivalist' ? 1.0 : 0.9));
    }

    public function getCampingValues(Citizen $citizen): array {
        // In order to don't overflow 100%, we take the min between 0 and the camping value.
        // Camping value is going more and more negative when your camping chances are dropping.
        // The survivalist job can reach 100% of camping survival chances. Others are stuck at 90%.

        $zone = $citizen->getZone();

		// Generic infos
		$is_panda = $citizen->getTown()->getType()->getName() === 'panda';
		$has_pro_camper = $citizen->property( CitizenProperties::EnableProCamper );
		$config = $this->conf->getTownConfiguration($citizen->getTown());
		$has_scout_protection = $this->inventory_handler->countSpecificItems(
			$citizen->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
		) > 0;
        $previous_campers = $this->entity_manager->getRepository(Zone::class)->findPreviousCampersCount($citizen);

		$chance = [
			'previous' => 0,
			'tomb' => 0,
			'town' => 0,
			'zone' => 0,
			'zoneBuilding' => 0,
			'lighthouse' => 0,
			'campitems' => 0,
			'zombies' => 0,
			'campers' => 0,
			'night' => 0,
			'distance' => 0,
			'devastated' => 0
		];
		// Previous campings
		if( $is_panda )
			if( $has_pro_camper )
				$campChances = [50,45,40,30,20,10,0];
			else
				$campChances = [50,30,20,10,0];
		else
			if( $has_pro_camper )
				$campChances = [80,70,60,40,30,20,0];
			else
				$campChances = [80,60,35,15,0];
		$campChances = array_merge($campChances, [-50, -100, -200, -400, -1000, -2000, -5000] );
		$chance['previous'] = $campChances[min($citizen->getCampingCounter(), count($campChances) - 1)];

		// Tomb bonus
		$chance["tomb"] = ($citizen->getStatus()->contains( $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' ) ) ? 8 : 0);

		// Hardcore malus
		$chance["town"] = (int)$config->get(TownConf::CONF_MODIFIER_CAMPING_BONUS, 0);

		// Zone improvement
		$chance["zone"] = $zone->getImprovementLevel();

        // Ruin
        $chance["zoneBuilding"] = $this->getZoneBuildingBonus($citizen);

		// Lighthouse
		if ($this->container->get(TownHandler::class)->getBuilding( $citizen->getTown(), "small_lighthouse_#00", true ))
			$chance["lighthouse"] = 25;

		// Camping items in the backpack
		$campitems = [
			$this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'smelly_meat_#00' ),
			$this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'sheet_#00' ),
		];
		$chance['campitems'] = $this->inventory_handler->countSpecificItems($citizen->getInventory(), $campitems, false, false) * 5; // Each item gives a 5% bonus

		// Zombies on the zone
		$zombieRatio = ($has_scout_protection ? -3 : -7);
		$chance["zombies"] = $zone->getZombies() * $zombieRatio;

		// Other campers on the zone
		if ($previous_campers > 0) {
            // Map previous_campers to a camping malus
			$crowdChances = [0,0,-10,-30,-50,-70];
			$cc = $crowdChances[ min(count($crowdChances) - 1, max(0, $previous_campers))];
			$chance["campers"] = $cc;
		}

		// Night
		$camping_datetime = new DateTime();
		if ($citizen->getCampingTimestamp() > 0)
			$camping_datetime->setTimestamp( $citizen->getCampingTimestamp() );
		if ($config->isNightMode(ignoreNightModeConfig: true))
			$chance['night'] = 10;

		// Zone distance
		$distChances = [-100, -75, -50, -25, -10, 0, 0, 0, 0, 0, 0, 0, 5, 7, 10, 15, 20];
		$chance["distance"] = $distChances[ min(count($distChances) - 1, $zone->getDistance()) ];

		// Devastated town
		if ($citizen->getTown()->getDevastated())
			$chance["devastated"] = -50;

		return $chance;
		// OLD METHOD:
        // return min(max((100.0 - (abs(min(0, array_sum($this->getCampingValues($citizen)))) * 5)) / 100.0, .1), $citizen->getProfession()->getName() === 'survivalist' ? 1.0 : 0.9);
    }

    /**
     * Obtain the currently available camping bonus provided by a ruin in this area.
     * Takes into account the current camping priority of the provided citizen.
     */
    public function getZoneBuildingBonus(Citizen $citizen) {
        $zone = $citizen->getZone();
        $ruin = $zone->getPrototype();

        // Empty desert default penalty
        $chance = -25;

        // Try to hide inside a building
		if ($this->canHideInsideCurrentBuilding($citizen)) {
            // Buried ruin bonus
            $chance = 15;

            // Ruin bonus
            if($zone->getBuryCount() == 0) {
                $chance = $ruin->getCampingLevel();
            }
		}

        return $chance;
    }

    /**
     * Checks if the citizen is able to hide inside the building.
     * If the citizen is already hidden, takes into account his camping priority.
     */
    public function canHideInsideCurrentBuilding(Citizen $citizen): bool {
        $zone = $citizen->getZone();
        $ruin = $zone->getPrototype();

        // No building to hide inside
        if(!$ruin) return false;

        $previous_campers = $this->entity_manager->getRepository(Zone::class)->findPreviousCampersCount($citizen);
        $capacity = $zone->getBuildingCampingCapacity();

        return $capacity < 0 || $previous_campers < $capacity;
    }

    public function getNightwatchProfessionDefenseBonus(Citizen $citizen): int{
        /*if ($citizen->getProfession()->getName() == "guardian") {
            return 30;
        } else if ($citizen->getProfession()->getName() == "tamer") {
            return 20;
        }*/

        return $citizen->getProfession()->getNightwatchDefenseBonus();
    }

    public function getNightwatchProfessionSurvivalBonus(Citizen $citizen): float{
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

        $is_pro = $citizen->property(CitizenProperties::EnableProWatchman);

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

    public function getNightWatchDefense(Citizen $citizen): int {
        return $this->events->citizenQueryNightwatchInfo( $citizen )['def'] ?? 0;
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
        if ($c->getHome()->hasTag('lock')) return true;
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
        if($this->hasStatusEffect($citizen, 'tg_chk_forum_day')) $level++;
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