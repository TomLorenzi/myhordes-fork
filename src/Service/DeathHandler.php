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
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use App\Structures\BetweenFilter;

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


    public function __construct(
        EntityManagerInterface $em, StatusFactory $sf, ZoneHandler $zh, InventoryHandler $ih, CitizenHandler $ch, ItemFactory $if, LogTemplateHandler $lt, PictoHandler $ph, RandomGenerator $rg, ConfMaster $conf)
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
        if (is_int($cod)) $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneByRef( $cod );

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


        foreach ($this->entity_manager->getRepository(DigTimer::class)->findAllByCitizen($citizen) as $dt)
            $remove[] = $dt;
        foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
            $remove[] = $et;
        $citizen->getStatus()->clear();

        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        }

        $died_outside = $citizen->getZone() !== null;
        if (!$died_outside) {
            $zone = null;
            $citizen->getHome()->setHoldsBody( true );
            if ($this->conf->getTownConfiguration( $citizen->getTown() )->get(TownConf::CONF_MODIFIER_BONES_IN_TOWN, false))
                $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'),
                    in_array($cod->getRef(), [CauseOfDeath::Hanging, CauseOfDeath::FleshCage]) ? [$citizen->getTown()->getBank()] : [$citizen->getHome()->getChest(),$citizen->getTown()->getBank()]
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

        $gazette = $this->entity_manager->getRepository(Gazette::class)->findOneByTownAndDay($citizen->getTown(), ($citizen->getTown()->getDay() + ($cod->getId() == CauseOfDeath::NightlyAttack ? 0 : 1)));
        if($gazette !== null){
            $gazette->addVictim($citizen);
            $this->entity_manager->persist($gazette);
        }

        // Give soul point
        $days = $citizen->getSurvivedDays();
        $nbSoulPoints = $days * ( $days + 1 ) / 2;

        $citizen->getUser()->addSoulPoints($nbSoulPoints);

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
                    $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName($nameOfPicto);
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
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dcity_#00");
                break;
            case CauseOfDeath::Vanished:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_doutsd_#00");
                break;
            case CauseOfDeath::Dehydration:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dwater_#00");
                break;
            case CauseOfDeath::Addiction:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_ddrug_#00");
                break;
            case CauseOfDeath::Infection:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dinfec_#00");
                break;
            case CauseOfDeath::Hanging:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dhang_#00");
                break;
            case CauseOfDeath::Radiations:
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dnucl_#00");
                $pictoDeath2 = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dinfec_#00");
                break;
        }

        if($pictoDeath !== null) {
            $this->picto_handler->give_validated_picto($citizen, $pictoDeath);
        }

        if($pictoDeath2 !== null) {
            $this->picto_handler->give_validated_picto($citizen, $pictoDeath2);
        }

        $this->picto_handler->give_validated_picto($citizen, "r_ptame_#00", $this->citizen_handler->getSoulpoints($citizen));

        // Now that we are dead, we set persisted = 1 to pictos with persisted = 0
        // according to the day 5 / 8 rule
        $this->picto_handler->validate_picto($citizen);

        if ($died_outside) $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, $zone ) );

        CitizenRankingProxy::fromCitizen( $citizen, true );
        TownRankingProxy::fromTown( $citizen->getTown(), true );

        if ($handle_em) foreach ($remove as $r) $this->entity_manager->remove($r);

        // If the town is not small, spawn a soul
        if($citizen->getTown()->getType()->getName() != 'small') {
            $minDistance = max(4, $citizen->getTown()->getDay());
            $maxDistance = min($citizen->getTown()->getDay() + 6, 15);

            $spawnZone = $this->random_generator->pickLocationBetweenFromList($citizen->getTown()->getZones()->toArray(), $minDistance, $maxDistance);
            $soulItem = $this->item_factory->createItem( "soul_blue_#00");
            $this->inventory_handler->forceMoveItem($spawnZone->getFloor(), $soulItem);
        }
    }
}