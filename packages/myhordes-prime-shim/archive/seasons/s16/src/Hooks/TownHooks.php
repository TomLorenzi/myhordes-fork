<?php

namespace MyHordes\Prime\Hooks;

use App\Hooks\HooksCore;

class TownHooks extends HooksCore {
	public function hookHomeForeignDeadActions(...$args) {
		return $this->twig->render("@MyHordesPrime/town/home_foreign_actions.html.twig", ['owner' => $args[0][0]]);
	}

	public function hookHomeForeignDeadActionsJs(...$args) {
		return $this->twig->render("@MyHordesPrime/town/home_foreign_actions.js.twig", ['owner' => $args[0][0]]);
	}

	public function hookHomeForeignDisposalText(...$args) {
		return $this->twig->render("@MyHordesPrime/town/home_foreign_disposal_text.html.twig", ['owner' => $args[0][0]]);

	}
}