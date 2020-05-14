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
use App\Entity\Item;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Structures\ItemRequest;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CitizenHandler
{
    private $entity_manager;
    private $status_factory;
    private $item_factory;
    private $random_generator;
    private $inventory_handler;
    private $picto_handler;
    private $log;
    private $container;

    public function __construct(EntityManagerInterface $em, StatusFactory $sf, RandomGenerator $g, InventoryHandler $ih,
                                PictoHandler $ph, ItemFactory $if, LogTemplateHandler $lh, ContainerInterface $c)
    {
        $this->entity_manager = $em;
        $this->status_factory = $sf;
        $this->random_generator = $g;
        $this->inventory_handler = $ih;
        $this->picto_handler = $ph;
        $this->item_factory = $if;
        $this->log = $lh;
        $this->container = $c;
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

    public function isWounded(Citizen $citizen) {
        return $this->hasStatusEffect( $citizen, ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'], false );
    }

    public function inflictWound( Citizen &$citizen ) {
        if ($this->isWounded($citizen)) return;
        $ap_above_6 = $citizen->getAp() - $this->getMaxAP( $citizen );
        $citizen->addStatus( $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName(
            $this->random_generator->pick( ['wound1','wound2','wound3','wound4','wound5','wound6'] )
        ) );
        $citizen->addStatus($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('tg_meta_wound'));
        if ($ap_above_6 >= 0)
            $citizen->setAp( $this->getMaxAP( $citizen ) + $ap_above_6 );

        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_wound_#00');
        $this->picto_handler->give_picto($citizen, $pictoPrototype);
    }

    public function healWound( Citizen &$citizen ) {
        foreach ($citizen->getStatus() as $status)
            if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] ))
                $citizen->removeStatus( $status );
    }

    /**
     * @param Citizen $citizen
     * @param CitizenStatus|string $status
     * @return bool
     */
    public function inflictStatus( Citizen &$citizen, $status ): bool {
        if (is_string( $status )) $status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName($status);
        if (!$status) return false;

        if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] )) {
            $this->inflictWound($citizen);
            return true;
        }

        // Prevent thirst and infection for ghouls
        if ((   $status->getName() === 'thirst1' ||
                $status->getName() === 'thirst2' ||
                $status->getName() === 'infection'
            ) && $citizen->hasRole('ghoul'))
            return false;

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

        if (in_array( $status->getName(), ['tg_meta_wound','wound1','wound2','wound3','wound4','wound5','wound6'] )) {
            $this->healWound($citizen);
            return true;
        }

        $citizen->removeStatus( $status );
        return true;
    }

    public function increaseThirstLevel( Citizen $citizen ) {

        if ($citizen->hasRole('ghoul')) return;

        $lv2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('thirst2');
        $lv1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('thirst1');

        if ($citizen->getStatus()->contains( $lv2 )) {
            $this->container->get(DeathHandler::class)->kill($citizen, CauseOfDeath::Dehydration);
        } elseif ($citizen->getStatus()->contains( $lv1 )) {
            $this->removeStatus( $citizen, $lv1 );
            $this->inflictStatus( $citizen, $lv2 );
        } else $this->inflictStatus( $citizen, $lv1 );

    }

    public function updateBanishment( Citizen &$citizen, ?Building $gallows, ?Building $cage ): bool {

        if (!$citizen->getAlive() || $citizen->getTown()->getChaos()) return false;

        $action = false; $kill = false;
        if (!$citizen->getBanished()) {
            if ($this->entity_manager->getRepository(Complaint::class)->countComplaintsFor($citizen, Complaint::SeverityBanish) >= 8)
                $action = true;
        }

        if ($gallows || $cage) {
            $complaintNeeded = 8;
            // If the citizen is already shunned, we need 6 more complains to hang him
            if($citizen->getBanished())
                $complaintNeeded = 6;
            if ($this->entity_manager->getRepository(Complaint::class)->countComplaintsFor($citizen, Complaint::SeverityKill) >= $complaintNeeded)
                $action = $kill = true;
        }

        if ($action) {
            if (!$citizen->getBanished()) $this->entity_manager->persist( $this->log->citizenBanish( $citizen ) );
            $citizen->setBanished( true );

            // Disable escort on banishment
            if ($citizen->getEscortSettings()) {
                $this->entity_manager->remove($citizen->getEscortSettings());
                $citizen->setEscortSettings(null);
            }

            if (!$kill) {
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_ban_#00');
                $this->picto_handler->give_picto($citizen, $pictoPrototype);
            }

            /** @var Item[] $items */
            $items = [];
            $impound_prop = $this->entity_manager->getRepository(ItemProperty::class)->findOneByName( 'impoundable' );
            foreach ( $citizen->getInventory()->getItems() as $item )
                if ($item->getPrototype()->getProperties()->contains( $impound_prop ))
                    $items[] = $item;
            foreach ( $citizen->getHome()->getChest()->getItems() as $item )
                if ($item->getPrototype()->getProperties()->contains( $impound_prop ))
                    $items[] = $item;

            $bank = $citizen->getTown()->getBank();
            foreach ($items as $item) {
                $source = $item->getInventory();
                if ($this->inventory_handler->transferItem( $citizen, $item, $source, $bank, InventoryHandler::ModalityImpound ) === InventoryHandler::ErrorNone)
                    $this->entity_manager->persist( $this->log->bankItemLog( $citizen, $item, true ) );
            }

            // As he is shunned, we remove all the complaints
            $complaints = $this->entity_manager->getRepository(Complaint::class)->findByCulprit($citizen);
            foreach ($complaints as $complaint) {
                $this->entity_manager->remove($complaint);
            }
        }

        if ($kill) {
            $rem = [];
            if ($cage) {
                $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::FleshCage, $rem );
                $cage->setTempDefenseBonus( $cage->getTempDefenseBonus() + ( $citizen->getProfession()->getHeroic() ? 60 : 40 ) );
                $this->entity_manager->persist( $cage );
            }
            elseif ($gallows) {
                $this->container->get(DeathHandler::class)->kill( $citizen, CauseOfDeath::Hanging, $rem );
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_dhang_#00');
                $this->picto_handler->give_picto($ac, $pictoPrototype);
            }
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, null ) );
            foreach ($rem as $r) $this->entity_manager->remove( $r );
        }

        return $action;
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
            $citizen->addRole($role);

            if ($role->getName() === 'ghoul') {
                $this->removeStatus($citizen, 'thirst1');
                $this->removeStatus($citizen, 'thirst2');
                $this->removeStatus($citizen, 'infection');
                $citizen->setWalkingDistance(0);
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

    public function setAP(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null) {
        if ($max_bonus !== null)
            $citizen->setAp( max(0, min(max($this->getMaxAP( $citizen ) + $max_bonus, $citizen->getAp()), $relative ? ($citizen->getAp() + $num) : max(0,$num) )) );
        else $citizen->setAp( max(0, $relative ? ($citizen->getAp() + $num) : max(0,$num) ) );
        if ($citizen->getAp() == 0) $this->inflictStatus( $citizen, 'tired' );
        else $this->removeStatus( $citizen, 'tired' );
    }

    public function getMaxBP(Citizen $citizen) {
        return $citizen->getProfession()->getName() === 'tech' ? 6 : 0;
    }

    public function setBP(Citizen &$citizen, bool $relative, int $num, ?int $max_bonus = null) {
        if ($max_bonus !== null)
            $citizen->setBp( max(0, min(max($this->getMaxBP( $citizen ) + $max_bonus, $citizen->getBp()), $relative ? ($citizen->getBp() + $num) : max(0,$num) )) );
        else $citizen->setBp( max(0, $relative ? ($citizen->getBp() + $num) : max(0,$num) ) );
    }

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

    public function deductAPBP(Citizen &$citizen, int $ap) {
        if ($ap <= $citizen->getBp())
            $this->setBP( $citizen, true, -$ap );
        else {
            $ap -= $citizen->getBp();
            $this->setAP($citizen, true, -$ap);
            $this->setBP($citizen, false, 0);
        }
    }

    public function getCP(Citizen &$citizen): int {
        if ($this->hasStatusEffect( $citizen, 'terror', false )) $base = 0;
        else {
            $base = $citizen->getProfession()->getName() == 'guardian' ? 4 : 2;

            $has_clean_body = true; // TODO: Add hero experience clean body
            $has_body_armor = true; // TODO: Add hero experience body armor

            if ($citizen->getProfession()->getHeroic() 
                    && $this->hasStatusEffect( $citizen, 'clean', false ) 
                    && $has_clean_body)
                $base += 1;

            if ($citizen->getProfession()->getHeroic() 
                    && $has_body_armor)
                $base += 1;

            if (!empty($this->inventory_handler->fetchSpecificItems(
                $citizen->getInventory(), [new ItemRequest( 'car_door_#00' )]
            ))) $base += 1;
        }

        if ($citizen->hasRole('guide'))
            $base += $citizen->getZone() ? $citizen->getZone()->getCitizens()->count() : 0;

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

        $citizen->setProfession( $profession );

        if ($profession->getName() === 'tech')
            $this->setBP($citizen,false, $this->getMaxBP( $citizen ),0);
        else $this->setBP($citizen, false, 0);

        $this->setPM($citizen, false, 0);

        if ($profession->getName() !== 'none')
            $this->entity_manager->persist( $this->log->citizenProfession( $citizen ) );

    }

    public function getSoulpoints(Citizen $citizen): int {
        $days = $citizen->getSurvivedDays();
        return $days * ( $days + 1 ) / 2;
    }

    public function getCampingChance(Citizen $citizen): float {
        $total_value = array_sum($this->getCampingValues($citizen));

        if ($total_value >= 0 && $citizen->getProfession()->getName() == 'survivalist') {
            $survival_chance = 1;
        }
        else if ($total_value > -2 && $citizen->getProfession()->getName() == 'survivalist') {
            $survival_chance = .95;
        }
        else if ($total_value > -4) {
            $survival_chance = .9;
        }
        else if ($total_value > -7) {
            $survival_chance = .75;
        }
        else if ($total_value > -10) {
            $survival_chance = .6;
        }
        else if ($total_value > -14) {
            $survival_chance = .45;
        }
        else if ($total_value > -18) {
            $survival_chance = .3;
        }
        else {
            $survival_chance = .15;
        }

        return $survival_chance;
    }

    public function getCampingValues(Citizen $citizen): array {
        $camping_values = [];
        $zone = $citizen->getZone();
        $town = $citizen->getTown();

        // Town type: Pandemonium gets malus of 14, all other types are neutral.
        $camping_values['town'] = $town->getType()->getId() == 3 ? -14 : 0;

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
            12 => -6,
            13 => -7,
            14 => -7,
            15 => -6,
        ];
        $zone_distance = round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));
        if ($zone_distance >= 16) {
            $camping_values['distance'] = -5;
        }
        else {
            $camping_values['distance'] = $distance_map[$zone_distance];
        }

        // Ruin in zone.
        $camping_values['ruin'] = $zone->getPrototype() ? $zone->getPrototype()->getCampingLevel() : 0;

        // Zombies in zone. Factor -1.4, for CamperPro it will -0.6.
        $camping_values['zombies'] = -1.4 * $zone->getZombies();

        // Zone improvement level.
        $camping_values['improvement'] = $zone->getImprovementLevel();

        // Previous camping count.
        $campings_map = [
            'normal' => [
                0 => 0,
                1 => -4,
                2 => -9,
                3 => -13,
                4 => -16,
                5 => -26,
                6 => -36,
            ],
            'hard' => [
                0 => 0,
                1 => -4,
                2 => -6,
                3 => -8,
                4 => -10,
            ],
        ];
        $previous_campings = $citizen->getCampingCounter();
        if ($town->getType()->getId() == 3) {
            $camping_values['campings'] = $campings_map['hard'][$previous_campings];
        }
        else {
            $camping_values['campings'] = $campings_map['normal'][$previous_campings];
        }

        // Campers that are already hidden.
        $campers_map = [
            0 => 0,
            1 => 0,
            2 => -2,
            3 => -5,
            4 => -10,
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
        if ($previous_campers >= 5) {
            $camping_values['campers'] = -14;
        }
        else {
            $camping_values['campers'] = $campers_map[$previous_campers];
        }

        // Hautfetzen + Zeltplanen
        $campitems = [
            $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'smelly_meat_#00' ),
            $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'sheet_#00' ),
        ];
        $camping_values['campitems'] = $this->inventory_handler->countSpecificItems($citizen->getInventory(), $campitems);

        // Grab
        $camping_values['tomb'] = 0;
        if ($citizen->getStatus()->contains( $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' ) )) {
            $camping_values['tomb'] = 1.9;
        }

        // Night time bonus.
        $camping_values['night'] = 0;
        $camping_datetime = new DateTime();
        $camping_datetime->setTimestamp( $citizen->getCampingTimestamp() );
        if ($camping_datetime->format('G') >= 19) {
            $camping_values['night'] = 2;
        }

        // Leuchtturm
        $camping_values['lighthouse'] = 0;
        if ($town->getBuildings()->contains( $this->entity_manager->getRepository(BuildingPrototype::class)->findOneByName( 'small_lighthouse_#00' )) ) {
            $camping_values['lighthouse'] = 5;
        }

        // Devastated town.
        $camping_values['devastated'] = $town->getDevastated() ? -10 : 0;

        return $camping_values;
    }

    public function getNightwatchProfessionDefenseBonus(Citizen $citizen){
        if ($citizen->getProfession()->getName() == "guardian") {
            return 30;
        } else if ($citizen->getProfession()->getName() == "tamer") {
            return 20;
        }
        return 0;
    }

    public function getNightwatchProfessionSurvivalBonus(Citizen $citizen){
        if ($citizen->getProfession()->getName() == "guardian") {
            return 0.04;
        }
        return 0;
    }

    public function getDeathChances(Citizen $citizen): float {
        $baseChance = 0.05;
        $baseChance -= $this->getNightwatchProfessionSurvivalBonus($citizen);

        $town = $citizen->getTown();

        $chances = $baseChance;
        for($i = 0 ; $i < $citizen->getTown()->getDay() - 1; $i++){
            $previousWatches = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchOfCitizenForADay($citizen, $i + 1);
            if($previousWatches === null) {
                $chances = max($baseChance, $chances -= 0.05);
            } else {
                $chances = min(1, $chances += 0.1);
            }
        }

        if($this->hasStatusEffect($citizen, "drunk")) {
            $chances -= 0.04;
        }
        if($this->hasStatusEffect($citizen, "hangover")) {
            $chances += 0.05;
        }
        if($this->hasStatusEffect($citizen, "terror")) {
            $chances += 0.45;
        }
        if($this->hasStatusEffect($citizen, "addict")) {
            $chances += 0.1;
        }
        if($this->isWounded($citizen)) {
            $chances += 0.20;
        }
        if($this->hasStatusEffect($citizen, "healed")) {
            $chances += 0.10;
        }
        if($this->hasStatusEffect($citizen, "infection")) {
            $chances += 0.20;
        }
        if($this->hasStatusEffect($citizen, "ghul")) {
            $chances -= 0.05;
        }

        return $chances;
    }

    public function getNightWatchDefense(Citizen $citizen): int {
        $def = 10;
        $def += $this->getNightwatchProfessionDefenseBonus($citizen);

        if($this->hasStatusEffect($citizen, 'drunk')) {
            $def += 20;
        }
        if($this->hasStatusEffect($citizen, 'hangover')) {
            $def -= 15;
        }
        if($this->hasStatusEffect($citizen, 'terror')) {
            $def -= 30;
        }
        if($this->hasStatusEffect($citizen, 'drugged')) {
            $def += 10;
        }
        if($this->hasStatusEffect($citizen, 'addict')) {
            $def += 15;
        }
        if($this->isWounded($citizen)) {
            $def -= 20;
        }
        if($this->hasStatusEffect($citizen, 'healed')) {
            $def -= 10;
        }
        if($this->hasStatusEffect($citizen, 'infection')) {
            $def -= 15;
        }
        if($this->hasStatusEffect($citizen, 'thirst2')) {
            $def -= 10;
        }
        foreach ($citizen->getInventory()->getItems() as $item) {
            $itemWatchPoints = $item->getPrototype()->getWatchpoint();
            $def += $itemWatchPoints;
        }
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
        if ($this->inventory_handler->countSpecificItems( $c->getHome()->getChest(), 'lock', true ) > 0)
            return true;
        return false;
    }

    public function hasNewMessage(Citizen $c){
        foreach ($c->getPrivateMessageThreads() as $thread) {
            if($thread->getArchived()) continue;
            if($thread->getNew())
                return true;
        }

        return false;
    }
}