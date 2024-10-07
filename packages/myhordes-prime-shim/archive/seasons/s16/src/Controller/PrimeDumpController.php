<?php

namespace MyHordes\Prime\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\CustomAbstractCoreEventController;
use App\Entity\ItemPrototype;
use App\Service\EventFactory;
use App\Service\JSONRequestParser;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveExecuteEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/prime/rest/v1/town/facilities/dump', name: 'rest_town_facilities_dump_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
#[Semaphore('town', scope: 'town')]
class PrimeDumpController extends CustomAbstractCoreEventController {

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	#[Route(path: '', name: 'retrieve', methods: ['POST'])]
	public function retrieve(EventFactory $e, JSONRequestParser $parser, EntityManagerInterface $em): JsonResponse {
		return $this->processEventChain(
			$e->gameInteractionEvent( DumpRetrieveCheckEvent::class )->setup($em->getRepository(ItemPrototype::class)->find($parser->get('id')), $parser->get('ap')),
			DumpRetrieveExecuteEvent::class
		);
	}
}