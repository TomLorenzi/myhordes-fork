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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/town/facilities/well", name="rest_town_facilities_well_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
 * @Semaphore("town", scope="town")
 */
class WellController extends CustomAbstractCoreEventController
{
    /**
     * @Route("", name="retrieve", methods={"GET"})
     * @param EventFactory $e
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function retrieve(EventFactory $e): JsonResponse {
        return $this->processEventChain(
            $e->gameInteractionEvent( WellExtractionCheckEvent::class )->setup( 1 ),
            WellExtractionExecuteEvent::class
        );
    }

    /**
     * @Route("", name="insert", methods={"PUT"})
     * @param EventFactory $e
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function insert(EventFactory $e): JsonResponse {
        return $this->processEventChain(
            $e->gameInteractionEvent( WellInsertionCheckEvent::class ),
            WellInsertionExecuteEvent::class
        );
    }
}
