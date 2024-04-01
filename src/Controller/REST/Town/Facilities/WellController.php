<?php

namespace App\Controller\REST\Town\Facilities;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\CustomAbstractCoreEventController;
use App\Event\Game\Town\Basic\Well\WellExtractionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellExtractionExecuteEvent;
use App\Event\Game\Town\Basic\Well\WellInsertionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellInsertionExecuteEvent;
use App\Service\EventFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/town/facilities/well', name: 'rest_town_facilities_well_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[Semaphore('town', scope: 'town')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
class WellController extends CustomAbstractCoreEventController
{
    /**
     * @param EventFactory $e
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: '', name: 'retrieve', methods: ['GET'])]
    public function retrieve(EventFactory $e): JsonResponse {
        return $this->processEventChain(
            $e->gameInteractionEvent( WellExtractionCheckEvent::class )->setup( 1 ),
            WellExtractionExecuteEvent::class
        );
    }

    /**
     * @param EventFactory $e
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: '', name: 'insert', methods: ['PUT'])]
    public function insert(EventFactory $e): JsonResponse {
        return $this->processEventChain(
            $e->gameInteractionEvent( WellInsertionCheckEvent::class ),
            WellInsertionExecuteEvent::class
        );
    }
}
