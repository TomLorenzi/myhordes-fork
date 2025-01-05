<?php

namespace App\Hooks;

use App\Entity\Citizen;

class HomeHooks extends HooksCore {
	public function hookHomeDecoValues(array $args): string
    {
		/** @var Citizen $citizen */
        [$citizen] = $args;

		return $this->twig->render('partials/hooks/town/home_deco.html.twig', [
            'tags' => $citizen->getHome()->getAllTags(),
        ]);
	}
}