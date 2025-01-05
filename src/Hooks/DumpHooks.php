<?php

namespace App\Hooks;

use App\Entity\User;
use App\Service\TownHandler;

class DumpHooks extends HooksCore {
    public function hookDumpDisplayCost(array $args): string {
        $ap_cost = $args[0];
        $townHandler = $this->container->get(TownHandler::class);
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $free_dump = $townHandler->getBuilding($user->getActiveCitizen()->getTown(), 'small_trashclean_#00');
        return $this->twig->render('partials/hooks/dump/display_cost.html.twig', ['ap_cost' => $ap_cost, 'improved_dump_built' => $free_dump]);
    }

    public function hookDumpDisplayItems(array $args): string {
        $item = $args[0];
        $banished = $args[1];
        return $this->twig->render('partials/hooks/dump/item.html.twig', ['item' => $item, 'banished' => $banished]);
    }

    public function hookDumpDisplayActionsJs(...$args): string {
        return $this->twig->render('partials/hooks/dump/scripts.js.twig');
    }
}