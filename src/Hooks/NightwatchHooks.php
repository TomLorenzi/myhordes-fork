<?php

namespace App\Hooks;

use App\Entity\User;
use App\Service\TownHandler;

class NightwatchHooks extends HooksCore {
	public function hookNightwatchHeader(array $args): string {
		/** @var User $user */
		$user = $this->tokenStorage->getToken()->getUser();
		$townHandler = $this->container->get(TownHandler::class);
		if (!$townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_armor_#00'))
			return $this->twig->render('partials/hooks/nightwatch/header.html.twig');

		return '';
	}
}