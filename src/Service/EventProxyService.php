<?php

namespace App\Service;

use App\Entity\Building;
use App\Entity\Citizen;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
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
}