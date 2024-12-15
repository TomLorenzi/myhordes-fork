<?php

namespace MyHordes\Prime\Hooks;

use App\Entity\User;
use App\Hooks\HooksCore;
use App\Service\TownHandler;

class DumpHooks extends HooksCore {

	function hookDumpDisplayCost(array $args): string {
		$ap_cost = $args[0];
		$townHandler = $this->container->get(TownHandler::class);
		/** @var User $user */
		$user = $this->tokenStorage->getToken()->getUser();
		$free_dump = $townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_trashclean_#00');
		return $this->twig->render('@MyHordesPrime/dump/display_cost.html.twig', ['ap_cost' => $ap_cost, 'improved_dump_built' => $free_dump]);
	}

	function hookDumpDisplayItems(array $args): string {
		$item = $args[0];
		$banished = $args[1];
		return $this->twig->render('@MyHordesPrime/dump/item.html.twig', ['item' => $item, 'banished' => $banished]);

	}

	function hookDumpDisplayActionsJs(...$args): string {
		return $this->twig->render('@MyHordesPrime/dump/scripts.js.twig');
	}
}