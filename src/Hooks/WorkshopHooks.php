<?php

namespace App\Hooks;

use App\Entity\User;
use App\Service\TownHandler;

class WorkshopHooks extends HooksCore {
	public function hookWorkShopTech(array $args): string {
		/** @var User $user */
		$user = $this->tokenStorage->getToken()->getUser();
		$townHandler = $this->container->get(TownHandler::class);
		if ($townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_techtable_#00'))
			return $this->twig->render('partials/hooks/town/workshop.html.twig');

		return '';
	}
}