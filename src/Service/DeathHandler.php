<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ConsecutiveDeathMarker;
use App\Entity\EscapeTimer;
use App\Entity\Gazette;
use App\Entity\HomeIntrusion;
use App\Entity\PictoPrototype;
use App\Entity\RuinZone;
use App\Entity\TownRankingProxy;
use App\Entity\UserGroup;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class DeathHandler
{
    private EntityManagerInterface $entity_manager;
    private ItemFactory $item_factory;
    private InventoryHandler $inventory_handler;
    private CitizenHandler $citizen_handler;
    private ZoneHandler $zone_handler;
    private PictoHandler $picto_handler;
    private LogTemplateHandler $log;
    private RandomGenerator $random_generator;
    private ConfMaster $conf;
    private PermissionHandler $perm;
    private GameProfilerService $gps;
    private EventProxyService $events;

    public function __construct(
        EntityManagerInterface $em, ZoneHandler $zh, InventoryHandler $ih, CitizenHandler $ch,
        ItemFactory $if, LogTemplateHandler $lt, PictoHandler $ph, RandomGenerator $rg, ConfMaster $conf,
        PermissionHandler $perm, GameProfilerService $gps, EventProxyService $events)
    {
        $this->entity_manager = $em;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->log = $lt;
        $this->random_generator = $rg;
        $this->conf = $conf;
        $this->perm = $perm;
        $this->gps = $gps;
        $this->events = $events;
    }

    /**
     * Process the death of a citizen
     * @param Citizen $citizen
     * @param int|CauseOfDeath $cod
     * @param array|null $remove
     */
    public function kill(Citizen $citizen, CauseOfDeath|int $cod, ?array &$remove = null, ?int $gazetteDay = null): void {
        $handle_em = $remove === null;
        $remove = [];
        if (!$citizen->getAlive()) return;
        if (is_int($cod)) $cod = $this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy( ['ref' => $cod] );

        $rucksack = $citizen->getInventory();

        $floor = ($citizen->getZone() ?
            (!$citizen->getZone()->isTownZone() ? $citizen->getZone()->getFloor() : $citizen->getTown()->getBank()) :
            $citizen->getHome()->getChest());
        if ($citizen->activeExplorerStats()) {
            /** @var RuinZone $ruinZone */
            $ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByExplorerStats($citizen->activeExplorerStats());
            // $floor = $citizen->activeExplorerStats()->getInRoom() ? $ruinZone->getRoomFloor() : $ruinZone->getFloor();
            $floor = $ruinZone->getFloor();
        }

        // Calculate decoration
        $deco = $this->citizen_handler->getDecoPoints($citizen);

        foreach ($rucksack->getItems() as $item)
            // We get his rucksack and drop items into the floor or into his chest (except job item)
            if(!$item->getEssential())
                $this->inventory_handler->forceMoveItem($floor, $item);


        foreach ($citizen->getDigTimers() as $dt)
            $remove[] = $dt;
        foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
            $remove[] = $et;

        // If the citizen is marked to become a ghoul after the next attack, pass the mark on to another
        // citizen
        if ($this->citizen_handler->hasStatusEffect($citizen, 'tg_air_infected') || $this->citizen_handler->hasStatusEffect($citizen, 'tg_air_ghoul'))
            $this->citizen_handler->pass_airborne_ghoul_infection($citizen);

        $survivedDays = max(0, $citizen->getTown()->getDay() - 1);
        $citizen->setSurvivedDays($survivedDays);
        $citizen->setDayOfDeath($citizen->getTown()->getDay());

        foreach ($citizen->getCitizenWatch() as $cw) {
            $citizen->getTown()->removeCitizenWatch($cw);
            $citizen->removeCitizenWatch($cw);
            $this->entity_manager->remove($cw);
        }

        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        }

        foreach ($citizen->getLeadingEscorts() as $escort)
            $this->entity_manager->persist( $escort->getCitizen()->getEscortSettings()->setLeader(null) );

        $died_outside = $citizen->getZone() !== null;
        if (!$died_outside) {
            $zone = null;
            $citizen->getHome()->setHoldsBody( true );
            if ($this->conf->getTownConfiguration( $citizen->getTown() )->get(TownConf::CONF_MODIFIER_BONES_IN_TOWN, false))
                $this->events->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'),
                    [$citizen->getHome()->getChest(),$citizen->getTown()->getBank()]
                );
        }
        else {
            $zone = $citizen->getZone(); $ok = $this->zone_handler->isZoneUnderControl( $zone );
            if ($cod->getRef() === CauseOfDeath::Vanished
             || $cod->getRef() === CauseOfDeath::GhulEaten) {
                if ($zone->isTownZone())
                    $this->inventory_handler->forceMoveItem(
                        $citizen->getTown()->getBank(),
                        $this->item_factory->createItem('bone_meat_#00')
                    );
                else
                    $this->events->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'), [$zone->getFloor()], true);
            }

            $citizen->setZone(null);
            $zone->removeCitizen( $citizen );
            if (!$zone->isTownZone()) $zone->setPlayerDeaths( $zone->getPlayerDeaths() + 1 );
            $this->zone_handler->handleCitizenCountUpdate( $zone, $ok );
            foreach ($this->entity_manager->getRepository(HomeIntrusion::class)->findBy(['victim' => $citizen]) as $homeIntrusion)
                $this->entity_manager->remove($homeIntrusion);
        }

        if($citizen->getBanished()){
            $this->events->placeItem( $citizen, $this->item_factory->createItem('banned_note_#00'), [$citizen->getHome()->getChest()], true);
        }

        $citizen->setCauseOfDeath($cod);
        $citizen->setAlive(false);
        $this->gps->recordCitizenDied($citizen);

        if ($citizen->getTown()->getDay() <= 3) {
            $cdm = $this->entity_manager->getRepository(ConsecutiveDeathMarker::class)->findOneBy( ['user' => $citizen->getUser()] )
                ?? (new ConsecutiveDeathMarker)->setUser($citizen->getUser())->setDeath( $cod )->setNumber(0);
            if ($cdm->getDeath() === $cod) $cdm->setNumber($cdm->getNumber()+1);
            else $cdm->setNumber(1)->setDeath($cod);

            $this->entity_manager->persist($cdm->setTimestamp(new \DateTime()));
        } elseif ($cdm = $this->entity_manager->getRepository(ConsecutiveDeathMarker::class)->findOneBy( ['user' => $citizen->getUser()] )) {
            $this->entity_manager->persist($cdm->setNumber(0)->setDeath($cod));
        }

        $gazetteDay ??= $citizen->getTown()->getDay() + (in_array($cod->getRef(), [CauseOfDeath::NightlyAttack,CauseOfDeath::Radiations]) ? 0 : 1);
        $gazette = $citizen->getTown()->findGazette( $gazetteDay, true );
        /** @var Gazette $gazette */
        if($gazette !== null){
            $gazette->addVictim($citizen);
            foreach ($citizen->getRoles() as $role) {
                if (!$role->getVotable()) continue;
                $gazette->addVotesNeeded($role);
            }
            $this->entity_manager->persist($gazette);
        }

        $this->entity_manager->persist( CitizenRankingProxy::fromCitizen( $citizen, true ) );
        $this->entity_manager->persist( TownRankingProxy::fromTown( $citizen->getTown(), true ) );

        // Give soul point
        if (!$this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GIVE_SOULPOINTS, true))
            $citizen->getRankingEntry()->setPoints(0);

        // Give special picto
        if(($picto = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_SURVIVAL_PICTO, null))) {
            $days = $citizen->getSurvivedDays();
            $nbPicto = pow(($days - 3), 1.5);
            $this->picto_handler->give_validated_picto($citizen, $picto, (int)floor($nbPicto));
        }

        // Add pictos
        if ($citizen->getSurvivedDays()) {
            // Job picto
            $job = $citizen->getProfession();
            if($job->getPictoName() !== null){
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $job->getPictoName()]);
                $this->picto_handler->give_validated_picto($citizen, $pictoPrototype, $citizen->getDayOfDeath() - 1);
            }

            // Clean picto
            if($citizen->getSurvivedDays() >= 3 && $this->citizen_handler->hasStatusEffect($citizen, "clean")) {
                // We earn the picto for the past days
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_nodrug_#00");
                $this->picto_handler->give_picto($citizen, $pictoPrototype, round(pow($citizen->getSurvivedDays(), 1.5), 0));
            }

            // Decoration picto
            if($deco > 0)
	           $this->picto_handler->give_validated_picto($citizen, "r_deco_#00", $deco);
        }

        foreach ($cod->getPictos() as $pictoDeath) {
            $this->picto_handler->give_validated_picto($citizen, $pictoDeath);
        }

        if (!$this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_GIVE_SOULPOINTS, true))
            $sp = 0;
        else $sp = $this->citizen_handler->getSoulpoints($citizen);
        
        if($sp > 0)
            $this->picto_handler->give_validated_picto($citizen, "r_ptame_#00", $sp);

        // Now that we are dead, we set persisted = 1 to pictos with persisted = 0
        // according to the day 5 / 8 rule
        $this->picto_handler->validate_picto($citizen);

        if ($died_outside) $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, $zone ) );

        $citizen->getStatus()->clear();

        $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $citizen->getTown()->getId()] );
        if ($town_group) $this->perm->disassociate( $citizen->getUser(), $town_group );

        if ($handle_em) foreach ($remove as $r) $this->entity_manager->remove($r);

        // If the souls are enabled, spawn a soul
        $this->events->citizenPostDeath($citizen);
    }
}