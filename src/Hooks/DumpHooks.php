<?php

namespace App\Hooks;

use App\Entity\ItemPrototype;

class DumpHooks extends HooksCore {

	function hookDumpDisplayCost(...$args): string {
		$output = "";
		$ap_cost = $args[0][0];
		if ($ap_cost > 0) {
			$output .= '<div class="help warning">';
			$output .= "<b>" . $this->translator->trans('Achtung', [],'global'). " :</b> ";
			$output .= $this->translator->trans('Jeder so zerstörte Gegenstand kostet dich {ap}.', ['{ap}' => '<div class="ap">' . $ap_cost . '</div>'],'game');
			$output .= '<a class="help-button"><div class="tooltip help">' . $this->translator->trans('Diese Kosten können durch den Aufbau der <strong>Müll für Alle</strong>.',[],'global') . "</div>". $this->translator->trans('Hilfe', [],'global') . "</a>";
			$output .= '</div>';
		}
		return $output;
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