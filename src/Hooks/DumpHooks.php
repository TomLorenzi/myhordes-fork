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
		$proto = $item[0];
		/** @var ItemPrototype $proto */

		$output = '<div class="padded cell rw-5 rw-md-2 rw-sm-1">' .
                    '<span class="icon hide-md hide-sm">' .
                        "<img src='{$this->assets->getUrl('build/images/item/item_' . $proto->getIcon() . '.gif')}'  alt='{$this->translator->trans($proto->getLabel(), [], 'items')}' />" .
                        $this->translator->trans($proto->getLabel(), [], 'items') .
                    '</span>' .
                    "<img class='hide-lg hide-desktop' src='{$this->assets->getUrl('build/images/item/item_' . $proto->getIcon() . '.gif')}' alt='{{ proto.label|trans({},'items') }}' />".
                '</div>' .
                '<div class="padded cell rw-2 center">' .
                    "<span class='small'> {$item[1]}</span>" .
                '</div>' .
                '<div class="padded cell rw-2 rw-md-3 center">' .
                    "<div class='defense'>+{$item[2]}</div>" .
                '</div>' .
                '<div class="padded cell rw-3 rw-md-5 rw-sm-6 right">' .
                    (!$banished ? "<button x-item-id='{$proto->getId()}'>{$this->translator->trans('Installieren', [], 'game')}</button>" : "") .
                '</div>';
		return $output;
	}

	function hookDumpDisplayActionsJs(...$args): string {
		return "$.html.addEventListenerAll( '[x-item-id]', 'click', function (e,elem) {
            let ap = null, valid = false;
            do {
                ap = window.prompt('". addslashes($this->translator->trans('Wie viele Gegenstände möchtest du auf den Müll werfen?', [], 'game'))."', '1');
                if (ap === null) valid = true;
                else {
                    ap = parseInt(ap);
                    valid = !isNaN(ap) && ap >= 0;
                }
            } while (!valid);

            if (!ap) return;

            $.ajax.easySend( '{$this->router->generate('rest_town_facilities_dump_insert')}', {id: parseInt(elem.getAttribute('x-item-id')), ap: ap},
                function () {
                    $.ajax.load(null, '{$this->router->generate('town_dump')}', true);
                } );
        } )";
	}
}