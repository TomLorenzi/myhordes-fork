<?php

namespace App\Hooks;

class DumpHooks extends HooksCore {

	function hookDumpDisplayCost(...$args): string {
		$ap_cost = $args[0][0];
		return $this->twig->render('partials/hooks/dump/display_cost.html.twig', ['ap_cost' => $ap_cost]);
	}

	function hookDumpDisplayItems(...$args): string {
		$item = $args[0][0];
		$banished = $args[0][1];
		return $this->twig->render('partials/hooks/dump/item.html.twig', ['item' => $item, 'banished' => $banished]);
	}

	function hookDumpDisplayActionsJs(...$args): string {
		return $this->twig->render('partials/hooks/dump/scripts.js.twig');
	}
}