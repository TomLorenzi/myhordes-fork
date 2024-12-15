<?php

namespace MyHordes\Prime\Hooks;

use App\Entity\User;
use App\Hooks\HooksCore;
use App\Service\TownHandler;

class WorkshopHooks extends HooksCore {
	public function hookWorkShopTech(array $args) {
		/** @var User $user */
		$user = $this->tokenStorage->getToken()->getUser();
		$townHandler = $this->container->get(TownHandler::class);
		if ($townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_techtable_#00'))
			return $this->twig->render('@MyHordesPrime/town/workshop.html.twig');

		return '';
	}
}