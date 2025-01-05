<?php

namespace App\Hooks;

class TownHooks extends HooksCore {
	public function hookHomeForeignDeadActions(array $args): string {
		return $this->twig->render('partials/hooks/town/home_foreign_actions.html.twig', ['owner' => $args[0]]);
	}

	public function hookHomeForeignDeadActionsJs(array $args): string {
		return $this->twig->render('partials/hooks/town/home_foreign_actions.js.twig', ['owner' => $args[0]]);
	}

	public function hookHomeForeignDisposalText(array $args): string {
		return $this->twig->render('partials/hooks/town/home_foreign_disposal_text.html.twig', ['owner' => $args[0]]);
	}

    public function hookGazetteFilterBuildingOptions(): string {
        return $this->twig->render('partials/hooks/town/gazette_filter.html.twig');
    }
}