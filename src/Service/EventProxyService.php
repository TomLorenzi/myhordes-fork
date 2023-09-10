<?php

namespace App\Service;

use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\RuinZone;
use App\Entity\Town;
use App\Entity\Zone;
use App\Enum\EventStages\BuildingEffectStage;
use App\Enum\ScavengingActionType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\Event\Game\Citizen\CitizenQueryDigChancesEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingDestructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePreAttackEvent;
use App\Structures\FriendshipActionTarget;
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
     * @param string $method
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingDestruction( Building $building, string $method = 'attack' ): void {
        $this->ed->dispatch( $this->ef->gameEvent( BuildingDestructionEvent::class, $building->getTown() )->setup( $building, $method ) );
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
     * @param BuildingEffectStage $stage
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildingEffect( Building $building, ?Building $upgraded, BuildingEffectStage $stage ): void {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->ed->dispatch($this->ef->gameEvent($stage->eventClass(), $building->getTown() )->setup($building,$upgraded) );
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

    /**
     * @param Citizen $citizen
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function citizenPostDeath( Citizen $citizen ): void {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CitizenPostDeathEvent::class, $citizen->getTown() )->setup( $citizen ) );
    }

    /**
     * @param Citizen $citizen
     * @param Zone|RuinZone|null $zone,
     * @param ScavengingActionType $type,
     * @param bool $night
     * @return float
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function citizenQueryDigChance( Citizen $citizen, Zone|RuinZone|null $zone, ScavengingActionType $type, bool $night ): float {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CitizenQueryDigChancesEvent::class, $citizen->getTown() )->setup( $citizen, $type, $zone, at_night: $night ) );
        return $event->chance;
    }

    /**

     */
    public function executeCustomAction( int $type, Citizen $citizen, ?Item $item, Citizen|Item|ItemPrototype|FriendshipActionTarget|null $target, ItemAction $action, ?string &$message, ?array &$remove, array &$execute_info_cache ): void {
        $this->ed->dispatch( $event = $this->ef->gameEvent( CustomActionProcessorEvent::class, $citizen->getTown() )->setup( $type, $citizen, $item, $target, $action, $message, $remove, $execute_info_cache ) );
        $message = $event->message;
        $remove = $event->remove;
        $execute_info_cache = $event->execute_info_cache;
    }
}