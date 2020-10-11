<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\Gazette;
use App\Entity\PictoPrototype;
use App\Entity\RuinZone;
use App\Entity\Soul;
use App\Entity\TownRankingProxy;
use App\Entity\UserGroup;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class DeathHandler
{
    private $entity_manager;
    private $status_factory;
    private $item_factory;
    private $inventory_handler;
    private $citizen_handler;
    private $zone_handler;
    private $picto_handler;
    private $log;
    private $random_generator;
    private $conf;
    private $perm;

    public function __construct(
        EntityManagerInterface $em, StatusFactory $sf, ZoneHandler $zh, InventoryHandler $ih, CitizenHandler $ch,
        ItemFactory $if, LogTemplateHandler $lt, PictoHandler $ph, RandomGenerator $rg, ConfMaster $conf,
        PermissionHandler $perm)
    {
        $this->entity_manager = $em;
        $this->status_factory = $sf;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->log = $lt;
        $this->random_generator = $rg;
        $this->conf = $conf;
        $this->perm = $perm;
    }

    /**
     * @param Citizen $citizen
     * @param CauseOfDeath|int $cod
     * @param array $remove
     */
    public function kill(Citizen &$citizen, $cod, ?array &$remove = null): void {
        $handle_em = $remove === null;
        $remove = [];
        if (!$citizen->getAlive()) return;
        if (is_int($cod)) $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => $cod] );

        $rucksack = $citizen->getInventory();

        $floor = ($citizen->getZone() ? $citizen->getZone()->getFloor() : $citizen->getHome()->getChest());
        if ($citizen->activeExplorerStats()) {
            $ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByExplorerStats($citizen->activeExplorerStats());
            $floor = $citizen->activeExplorerStats()->getInRoom() ? $ruinZone->getRoomFloor() : $ruinZone->getFloor();
        }

        foreach ($rucksack->getItems() as $item)
            // We get his rucksack and drop items into the floor or into his chest (except job item)
            if(!$item->getEssential())
                $this->inventory_handler->forceMoveItem($floor, $item);


        foreach ($citizen->getDigTimers() as $dt)
            $remove[] = $dt;
        foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
            $remove[] = $et;
        $citizen->getStatus()->clear();

        foreach ($citizen->getCitizenWatch() as $cw) {
            $citizen->getTown()->removeCitizenWatch($cw);
            $citizen->removeCitizenWatch($cw);
            $this->entity_manager->remove($cw);
        }

        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        }

        $died_outside = $citizen->getZone() !== null;
        if (!$died_outside) {
            $zone = null;
            $justice = in_array($cod->getRef(), [CauseOfDeath::Hanging, CauseOfDeath::FleshCage]);
            $citizen->getHome()->setHoldsBody( true );
            if ($justice || $this->conf->getTownConfiguration( $citizen->getTown() )->get(TownConf::CONF_MODIFIER_BONES_IN_TOWN, false))
                $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'),
                    $justice ? [$citizen->getTown()->getBank()] : [$citizen->getHome()->getChest(),$citizen->getTown()->getBank()]
                );
        }
        else {
            $zone = $citizen->getZone(); $ok = $this->zone_handler->check_cp( $zone );
            if ($zone->getX() === 0 && $zone->getY() === 0)
                $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'), [$citizen->getTown()->getBank()]);
            else
                $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'), [$zone->getFloor()]);

            $citizen->setZone(null);
            $zone->removeCitizen( $citizen );
            $this->zone_handler->handleCitizenCountUpdate( $zone, $ok );
        }

        if($citizen->getBanished()){
            $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('banned_note_#00'), [$citizen->getHome()->getChest()], true);
        }

        $citizen->setCauseOfDeath($cod);
        $citizen->setAlive(false);

        $gazette = $citizen->getTown()->findGazette( ($citizen->getTown()->getDay() + ($cod->getId() == CauseOfDeath::NightlyAttack ? 0 : 1)) );
        if($gazette !== null){
            $gazette->addVictim($citizen);
            $this->entity_manager->persist($gazette);
        }

        // Give soul point
        if($citizen->getTown()->getType()->getName() !== 'custom' || $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GIVE_SOULPOINTS, false)) {
            $days = $citizen->getSurvivedDays();
            $nbSoulPoints = $days * ( $days + 1 ) / 2;

            $citizen->getUser()->addSoulPoints($nbSoulPoints);
        }

        // Add pictos
        if ($citizen->getSurvivedDays()) {
            // Job picto
            $job = $citizen->getProfession();
            if($job->getHeroic()){
                $nameOfPicto = "";
                switch($job->getName()){
                    case "collec":
                        $nameOfPicto = "r_jcolle_#00";
                        break;
                    case "guardian":
                        $nameOfPicto = "r_jguard_#00";
                        break;
                    case "hunter":
                        $nameOfPicto = "r_jrangr_#00";
                        break;
                    case "tamer":
                        $nameOfPicto = "r_jtamer_#00";
                        break;
                    case "tech":
                        $nameOfPicto = "r_jtech_#00";
                        break;
                    case "survivalist":
                        $nameOfPicto = "r_jermit_#00";
                        break;
                }

                if($nameOfPicto != "") {
                    $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $nameOfPicto]);
                    $this->picto_handler->give_validated_picto($citizen, $pictoPrototype, $citizen->getSurvivedDays() - 1);
                }
            }

            // Clean picto
            if($citizen->getSurvivedDays() >= 3 && $this->citizen_handler->hasStatusEffect($citizen, "clean")) {
                // We earn for good the picto for the past days
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_nodrug_#00");
                $this->picto_handler->give_validated_picto($citizen, $pictoPrototype, round(pow($citizen->getSurvivedDays() - 1, 1.5), 0));

                // We need to do the day 5 / day 8 rule calculation for the last day
                $this->picto_handler->give_picto($citizen, $pictoPrototype, round(pow($citizen->getSurvivedDays(), 1.5), 0) - round(pow($citizen->getSurvivedDays() - 1, 1.5), 0));
            }

            // Decoration picto
            // Calculate decoration
	        $deco = 0;
	        foreach ($citizen->getHome()->getChest()->getItems() as $item)
	            $deco += $item->getPrototype()->getDeco();

            if($deco > 0)
	           $this->picto_handler->give_validated_picto($citizen, "r_deco_#00", $deco);
        }

        $pictoDeath = null;
        $pictoDeath2 = null;
        switch ($cod->getRef()) {
            case CauseOfDeath::NightlyAttack:
                $pictoDeath = "r_dcity_#00";
                break;
            case CauseOfDeath::Vanished:
                $pictoDeath = "r_doutsd_#00";
                break;
            case CauseOfDeath::Dehydration:
                $pictoDeath = "r_dwater_#00";
                break;
            case CauseOfDeath::Addiction:
                $pictoDeath = "r_ddrug_#00";
                break;
            case CauseOfDeath::Infection:
                $pictoDeath = "r_dinfec_#00";
                break;
            case CauseOfDeath::Hanging:
                $pictoDeath = "r_dhang_#00";
                break;
            case CauseOfDeath::Radiations:
                $pictoDeath = "r_dnucl_#00";
                $pictoDeath2 = "r_dinfec_#00";
                break;
        }

        if($pictoDeath !== null)
            $this->picto_handler->give_validated_picto($citizen, $pictoDeath);

        if($pictoDeath2 !== null)
            $this->picto_handler->give_validated_picto($citizen, $pictoDeath2);
        $sp = $this->citizen_handler->getSoulpoints($citizen);
        
        if($sp > 0)
            $this->picto_handler->give_validated_picto($citizen, "r_ptame_#00", $sp);

        // Now that we are dead, we set persisted = 1 to pictos with persisted = 0
        // according to the day 5 / 8 rule
        $this->picto_handler->validate_picto($citizen);

        if ($died_outside) $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, $zone ) );

        CitizenRankingProxy::fromCitizen( $citizen, true );
        TownRankingProxy::fromTown( $citizen->getTown(), true );

        $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $citizen->getTown()->getId()] );
        if ($town_group) $this->perm->disassociate( $citizen->getUser(), $town_group );

        if ($handle_em) foreach ($remove as $r) $this->entity_manager->remove($r);
        // If the town is not small AND the souls are enabled, spawn a soul
        if($citizen->getTown()->getType()->getName() != 'small' && $this->conf->getTownConfiguration( $citizen->getTown() )->get(TownConf::CONF_FEATURE_SHAMAN_MODE, 'normal') != 'none') {
            $minDistance = min(4, $citizen->getTown()->getDay());
            $maxDistance = max($citizen->getTown()->getDay() + 6, 15);

            $spawnZone = $this->random_generator->pickLocationBetweenFromList($citizen->getTown()->getZones()->toArray(), $minDistance, $maxDistance);
            $soulItem = $this->item_factory->createItem( "soul_blue_#00");
            $this->inventory_handler->forceMoveItem($spawnZone->getFloor(), $soulItem);
        }
    }
}