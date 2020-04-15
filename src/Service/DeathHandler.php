<?php


namespace App\Service;


use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\CitizenStatus;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\WellCounter;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class DeathHandler
{
    private $entity_manager;
    private $status_factory;
    private $item_factory;
    private $inventory_handler;
    private $citizen_handler;
    private $zone_handler;
    private $log;

    public function __construct(
        EntityManagerInterface $em, StatusFactory $sf, ZoneHandler $zh, InventoryHandler $ih, CitizenHandler $ch, ItemFactory $if, LogTemplateHandler $lt)
    {
        $this->entity_manager = $em;
        $this->status_factory = $sf;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->citizen_handler = $ch;
        $this->log = $lt;
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
        foreach ($rucksack->getItems() as $item)
            if ( !$this->inventory_handler->moveItem($citizen, $rucksack, $item, $citizen->getZone() ? [$citizen->getZone()->getFloor()] : [$citizen->getHome()->getChest(), $citizen->getTown()->getBank()]) ) {
                $this->inventory_handler->forceRemoveItem( $item, PHP_INT_MAX );
            }

        foreach ($this->entity_manager->getRepository(DigTimer::class)->findAllByCitizen($citizen) as $dt)
            $remove[] = $dt;
        foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
            $remove[] = $et;
        $citizen->getStatus()->clear();

        $died_outside = $citizen->getZone() !== null;
        if (!$died_outside) {
            $zone = null;
            $citizen->getHome()->setHoldsBody( true );
            $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'),
                in_array($cod->getRef(), [CauseOfDeath::Hanging, CauseOfDeath::FleshCage]) ? [$citizen->getTown()->getBank()] : [$citizen->getHome()->getChest(),$citizen->getTown()->getBank()]
            );
        }
        else {
            $zone = $citizen->getZone(); $ok = $this->zone_handler->check_cp( $zone );
            $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem('bone_meat_#00'), [$zone->getFloor()]);
            $citizen->setZone(null);
            $zone->removeCitizen( $citizen );
            $this->zone_handler->handleCitizenCountUpdate( $zone, $ok );
        }

        $citizen->setCauseOfDeath($cod);
        $citizen->setAlive(false);

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
                    $picto = new Picto();
                    $picto->setPrototype($pictoPrototype)
                        ->setPersisted(2)
                        ->setTown($citizen->getTown())
                        ->setUser($citizen->getUser())
                        ->setCount($citizen->getSurvivedDays());

                    $this->entity_manager->persist($picto);
                }
            }

            // Clean picto
            if($citizen->getSurvivedDays() >= 3 && $this->citizen_handler->hasStatusEffect($citizen, "clean", true)) {
                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_nodrug_#00");
                $picto = new Picto();
                $picto->setPrototype($pictoPrototype)
                    ->setPersisted(2)
                    ->setTown($citizen->getTown())
                    ->setUser($citizen->getUser())
                    ->setCount(round(pow($citizen->getSurvivedDays(), 1.5), 0);

                $this->entity_manager->persist($picto);
            }
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
                $pictoDeath = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dhang_#00");
                $pictoDeath2 = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_dinfec_#00");
                break;
        }

        if($pictoDeath !== null) {
            $picto = new Picto();
            $picto->setPrototype($pictoDeath)
                ->setPersisted(2)
                ->setTown($citizen->getTown())
                ->setUser($citizen->getUser())
                ->setCount(1);

            $this->entity_manager->persist($picto);
        }

        if($pictoDeath2 !== null) {
            $picto = new Picto();
            $picto->setPrototype($pictoDeath2)
                ->setPersisted(2)
                ->setTown($citizen->getTown())
                ->setUser($citizen->getUser())
                ->setCount(1);

            $this->entity_manager->persist($picto);
        }

        // Set all picto of town as persisted
        $pictoRepository = $this->entity_manager->getRepository(Picto::class);

        $pendingPictosOfUser = $pictoRepository->findPendingByUser($citizen->getUser());
        foreach ($pendingPictosOfUser as $pendingPicto) {
            // The picto has been validated for the previous day
            // We validate it
            if ($pendingPicto->getPersisted() == 1) {
                $pendingPicto->setPersisted(2);
                $this->entity_manager->persist($pendingPicto);
            } else {
                // We check the day 5 / 8 rule to persist the picto or not
                // The picto **IS NOT DELETED HERE**. Instead, it is deleted upon death confirmation
                // To show "You could have earn those if you survived X more days"
                if ($citizen->getSurvivedDays() >= 5 || ($citizen->getUser()->getSoulPoints() >= 100 && $citizen->getTown()->getType()->getName() == "small" && $citizen->getSurvivedDays() >= 8)){
                    // We check if this picto has already been earned previously (such as Heroic Action, 1 per day)
                    $previousPicto = $pictoRepository->findPreviousDaysPictoByUserAndTownAndPrototype($citizen->getUser(), $citizen->getTown(), $pendingPicto->getPrototype());
                    if($previousPicto === null) {
                        // We do not have it, we set it as earned
                        $pendingPicto->setPersisted(2);
                        $this->entity_manager->persist($pendingPicto);
                    } else {
                        // We have it, we add the count to the previously earned
                        // And remove the picto from today
                        $previousPicto->setCount($previousPicto->getCount() + $pendingPicto->getCount());
                        $this->entity_manager->persist($previousPicto);
                        $this->entity_manager->remove($pendingPicto);
                    }
                }
            }
        }

        if ($died_outside) $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, $zone ) );

        if ($handle_em) foreach ($remove as $r) $this->entity_manager->remove($r);
    }
}