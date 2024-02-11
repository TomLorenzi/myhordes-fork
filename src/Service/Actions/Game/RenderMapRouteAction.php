<?php

namespace App\Service\Actions\Game;

use App\Entity\Citizen;
use App\Entity\ExpeditionRoute;
use App\Entity\Town;
use App\Entity\Zone;
use App\Entity\ZombieEstimation;
use App\Service\CitizenHandler;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class RenderMapRouteAction
{
    public function __construct(
        private EntityManagerInterface $em,
    ) { }

    /**
     * @param Town $town
     * @return array
     */
    public function __invoke(
        Town $town
    ): array
    {
        return array_map( fn(ExpeditionRoute $route) => [
            'id' => $route->getId(),
            'owner' => $route->getOwner()->getName(),
            'length' => $route->getLength(),
            'label' => $route->getLabel(),
            'stops' => array_map( fn(array $stop) => ['x' => $stop[0], 'y' => $stop[1]], $route->getData() ),
        ], $this->em->getRepository(ExpeditionRoute::class)->findByTown( $town ) );
    }
}