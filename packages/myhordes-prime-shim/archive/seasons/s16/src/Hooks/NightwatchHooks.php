<?php

namespace MyHordes\Prime\Hooks;

use App\Entity\User;
use App\Hooks\HooksCore;
use App\Service\TownHandler;

class NightwatchHooks extends HooksCore {
	public function hookNightwatchHeader(...$args) {
		/** @var User $user */
		$user = $this->tokenStorage->getToken()->getUser();
		$townHandler = $this->container->get(TownHandler::class);
		if (!$townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_armor_#00'))
			return $this->twig->render('@MyHordesPrime/nightwatch/header.html.twig');

		return '';
	}
}