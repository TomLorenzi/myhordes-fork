<?php

namespace App\Hooks;

use Symfony\Contracts\Translation\TranslatorInterface;

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
}