<?php

namespace App\Controller\REST\Town\Facilities;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\CustomAbstractCoreEventController;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionExecuteEvent;
use App\Service\EventFactory;
use App\Service\JSONRequestParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/town/facilities/dump", name="rest_town_facilities_dump_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
 * @Semaphore("town", scope="town")
 */
class DumpController extends CustomAbstractCoreEventController
{
    /**
     * @Route("", name="insert", methods={"POST"})
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function insert(EventFactory $e, JSONRequestParser $parser, EntityManagerInterface $em): JsonResponse {
        return $this->processEventChain(
            $e->gameInteractionEvent( DumpInsertionCheckEvent::class )->setup($em->getRepository(ItemPrototype::class)->find($parser->get('id')), $parser->get('ap')),
            DumpInsertionExecuteEvent::class
        );
    }
}
