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
    private $zone_handler;
    private $log;

    public function __construct(
        EntityManagerInterface $em, StatusFactory $sf, ZoneHandler $zh, InventoryHandler $ih, ItemFactory $if, LogTemplateHandler $lt)
    {
        $this->entity_manager = $em;
        $this->status_factory = $sf;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
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

        if ($died_outside) $this->entity_manager->persist( $this->log->citizenDeath( $citizen, 0, $zone ) );

        if ($handle_em) foreach ($remove as $r) $this->entity_manager->remove($r);
    }
}