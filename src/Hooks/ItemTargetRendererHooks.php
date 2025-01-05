<?php

namespace App\Hooks;

class ItemTargetRendererHooks extends HooksCore {
	public function hookTamerDogPopup(array $args): string {
        list($scout, $renderer, $target_for, $actions) = $args;

        $bring = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_1', 'hero_tamer_2', 'hero_tamer_1b', 'hero_tamer_2b'] ) );
        usort($bring, fn($a, $b) => $a['action']->getName() <=> $b['action']->getName());

        $dope = array_filter( $actions, fn($a) => $a['action']->getName() === 'hero_tamer_3' );
        $protect = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_4', 'hero_tamer_4b'] ) );

        $camp = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_5', 'hero_tamer_5b'] ) );
        usort($camp, fn($a, $b) => $a['action']->getName() <=> $b['action']->getName());

        $fetch = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_6', 'hero_tamer_6b', 'hero_tamer_9'] ) );
        $steal = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_7', 'hero_tamer_7b'] ) );
        $guard = array_filter( $actions, fn($a) => in_array( $a['action']->getName(), ['hero_tamer_8', 'hero_tamer_8b'] ) );

        return $this->twig->render('partials/hooks/item/tamerDogPopup.html.twig', [
            'scout' => $scout,
            'renderer' => $renderer,
            'target_for' => $target_for,

            'actions_bring' => $bring,
            'actions_protect' => $protect,
            'actions_camp' => $camp,
            'actions_fetch' => $fetch,
            'actions_steal' => $steal,
            'actions_guard' => $guard,
            'actions_dope' => $dope,
        ]);
	}
}