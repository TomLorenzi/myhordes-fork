<?php

namespace App\Service;

use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\Town;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePreAttackEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventProxyService
{
    public function __construct(
        private EventDispatcherInterface $ed,
        private EventFactory $ef
    ) { }

    /**
     * @param Building $building
     * @param string|Citizen|null $method
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingConstruction( Building $building, string|Citizen $method = null ): void {
        $this->ed->dispatch( $this->ef->gameEvent( BuildingConstructionEvent::class, $building->getTown() )->setup( $building, $method ) );
    }

    /**
     * @param Building $building
     * @param bool $isPreAttack
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingUpgrade( Building $building, bool $isPreAttack ): void {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->ed->dispatch($this->ef->gameEvent($isPreAttack ? BuildingUpgradePreAttackEvent::class : BuildingUpgradePostAttackEvent::class, $building->getTown() )->setup($building ) );
    }

    /**
     * @param Building $building
     * @param ?Building $upgraded
     * @param bool $isPreAttack
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingEffect( Building $building, ?Building $upgraded, bool $isPreAttack ): void {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->ed->dispatch($this->ef->gameEvent($isPreAttack ? BuildingEffectPreAttackEvent::class : BuildingEffectPostAttackEvent::class, $building->getTown() )->setup($building,$upgraded) );
    }

    /**
     * @param Town $town
     * @param Item $item
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingQueryNightwatchDefenseBonus( Town $town, Item $item ): int {
        $this->ed->dispatch( $event = $this->ef->gameEvent( BuildingQueryNightwatchDefenseBonusEvent::class, $town )->setup( $item ) );
        return $event->defense;
    }
}