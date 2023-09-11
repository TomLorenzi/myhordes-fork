<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HeroSkillDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            'manipulator' => ['name' => 'manipulator', 'title' => 'Tipp-Ex', 'description' => 'Du kannst 2 Mal pro Partie einen Registereintrag unkenntlich machen. Dazu musst du nur auf das kleine Icon "Fälschen" klicken. Dieses befindet sich links neben dem "störenden" Registereintrag. ;-)', 'icon' => 'small_falsify', 'daysNeeded' => 3],
            'clairvoyance' => ['name' => 'clairvoyance', 'title' => 'Hellseherei', 'description' => 'Du erfährst, wie aktiv ein bestimmter Bürger in deiner Stadt spielt. Du musst dazu lediglich bei ihm daheim vorbeischauen...', 'icon' => 'small_view', 'daysNeeded' => 7],
            'writer' => ['name' => 'writer', 'title' => 'Rundbrief', 'description' => 'Mit dieser Funktion kannst du alle Stadteinwohner auf einmal anschreiben. Du kannst damit in den Foren zudem Rollenspielthreads (RP) erstellen (Übertreibe es aber bitte nicht - Danke!).', 'icon' => 'item_rp_sheets', 'daysNeeded' => 16],
            'brothers' => ['name' => 'brothers', 'title' => 'Freundschaft', 'description' => 'Mit dieser Fähigkeit kannst du 1 Mal pro Partie (Stadt) jemandem eine deiner noch ungenutzten Heldentaten schenken! Die anderen werden in dir den Messias erkennen! BEACHTE: Verschenkte Heldentaten kannst du in dieser Stadt nicht mehr selbst verwenden. Um jemandem eine Heldentat zu schenken, musst mit ihm in der gleichen Zone stehen oder, wenn du in der Stadt bist, ihn daheim besuchen.', 'icon' => 'r_share', 'daysNeeded' => 25],
            'dictator' => ['name' => 'dictator', 'title' => 'Diktator', 'description' => 'Schreibe auf die Tafel, die sich auf der Übersichtsseite deiner Stadt befindet. Darüberhinaus kannst du auch deinen Mitbürgern ein Bauprojekt empfehlen. Das Gebäude mit den meisten Empfehlungen wird auf der Bauseite der Stadt hervorgehoben.', 'icon' => 'small_chat', 'daysNeeded' => 31],
            'largechest1' => ['name' => 'largechest1', 'title' => 'Große Truhe', 'description' => 'Du erhältst einen zusätzlichen Platz in deiner Truhe.', 'icon' => 'item_home_box', 'daysNeeded' => 45],
            'healthybody' => ['name' => 'healthybody', 'title' => 'Top in Form', 'description' => 'Solange du "Clean" bist (sprich: Du hast in der Partie noch keine Drogen zu Dir genommen.), verfügst du über einen zusätzlichen Zonenkontrollpunkt in der Außenwelt.', 'icon' => 'status_clean', 'daysNeeded' => 61],
            'omniscience' => ['name' => 'omniscience', 'title' => 'Allwissenheit', 'description' => 'Allwissenheit funktioniert wie Hellseherei, außer, dass du jetzt eine Übersicht zur Aktivität aller Stadteinwohner bekommst. Klicke dazu in der Bürgerliste einfach auf den Button "Allwissenheit".', 'icon' => 'small_view', 'daysNeeded' => 75],
            'resourcefulness' => ['name' => 'resourcefulness', 'title' => 'Einfallsreichtum', 'description' => 'Du beginnst jede neue Stadt mit einem zusätzlichen nützlichen Gegenstand.', 'icon' => 'item_chest_hero', 'daysNeeded' => 91, 'items' => ['chest_hero_#00']],
            'largechest2' => ['name' => 'largechest2', 'title' => 'Doppelter Boden', 'description' => 'Du bekommst einen weiteren Platz in deiner Truhe.', 'icon' => 'item_home_box', 'daysNeeded' => 105],
            'luckyfind' => ['name' => 'luckyfind', 'title' => 'Schönes Fundstück', 'description' => 'Mit deiner Heldenfähigkeit "Fundstück" stöberst du jetzt NOCH BESSERE Gegenstände auf.', 'icon' => 'item_chest_hero', 'daysNeeded' => 121, 'hero_generic_find'],
            'largerucksack1' => ['name' => 'largerucksack1', 'title' => 'Aufgeräumte Tasche', 'description' => 'Du bekommst einen zusätzlichen Platz in deinem Rucksack.', 'icon' => 'item_bag', 'daysNeeded' => 135],
            'secondwind' => ['name' => 'secondwind', 'title' => 'Zweite Lunge', 'description' => 'Du verfügst ab sofort über eine mächtige Heldenfähigkeit, mit der du 6 AP wiederherstellen kannst und die deine Müdigkeit aufhebt.', 'icon' => 'small_pa', 'daysNeeded' => 151, 'hero_generic_ap'],
            'breakfast1' => ['name' => 'breakfast1', 'title' => 'Weitsichtig', 'description' => 'Du beginnst jede neue Stadt mit einer zusätzlichen Nahrungsmittelration.', 'icon' => 'item_food_bag', 'daysNeeded' => 165, 'items' => ['food_bag_#00']],
            'apag' => ['name' => 'apag', 'title' => 'Profi-Fotograph', 'description' => 'Du beginst jede neue Stadt mit einer Kamera aus Vorkriegstagen.', 'icon' => 'f_cam', 'daysNeeded' => 181],
            'brick' => ['name' => 'brick', 'title' => 'Panzerschrank', 'description' => 'Dank deiner zahlreichen Zombiekontakte verfügst du in der Außenwelt ab sofort über einen zusätzlichen Zonenkontrollpunkt.', 'icon' => 'item_shield', 'daysNeeded' => 195],
            'treachery' => ['name' => 'treachery', 'title' => 'Hinterhältigkeit', 'description' => 'Die "Tipp-Ex" Fähigkeit wird noch besser! Ab sofort kannst du pro Partie (Stadt) noch mehr Registereinträge fälschen! Du kannst ab sofort 4 Mal pro Partie einen Registereintrag unkenntlich machen.', 'icon' => 'small_falsify', 'daysNeeded' => 211],
            'cheatdeath' => ['name' => 'cheatdeath', 'title' => 'Den Tod besiegen', 'description' => 'Sobald diese neue Heldenfähigkeit aktiviert wurde, spürst du beim nächsten Zombieangriff keinen Durst, keine Drogenabhängigkeit und keine Infektion (nur eine Nacht lang gültig). Verhindert auch Ghulverhungern (Hungerbalken steigt trotzdem).', 'icon' => 'small_wrestle', 'daysNeeded' => 226, 'hero_generic_immune'],
            'revenge' => ['name' => 'revenge', 'title' => 'Süße Rache', 'description' => 'Solltest du am dritten Tag oder an einem späteren Zeitpunkt verbannt werden, bekommst du automatisch etwas Gift geschenkt, das du nach Belieben einsetzen kannst... Tja, man hätte dich besser nicht ärgern sollen!', 'icon' => 'item_april_drug', 'daysNeeded' => 241],
            'procamp' => ['name' => 'procamp', 'title' => 'Proficamper', 'description' => 'Die Nachteile, die bei wiederholtem Campen auftreten, fallen bei dir nicht mehr so stark aus: Somit kannst du in einer Stadt öfter campen.', 'icon' => 'small_camp', 'daysNeeded' => 301],
            'medicine1' => ['name' => 'medicine1', 'title' => 'Erfahrener Junkie', 'description' => 'Du beginnst jede neue Stadt mit einer Ration Paracetoid 7g in deinem Rucksack.', 'icon' => 'item_disinfect', 'daysNeeded' => 361, 'items' => ['disinfect_#00']],
            'mayor' => ['name' => 'mayor', 'title' => 'Bürgermeister', 'description' => 'Du kannst Privatstädte gründen (nach deinem nächsten Tod, auf der Seite "Spielen").', 'icon' => 'item_map', 'daysNeeded' => 541],
            'architect' => ['name' => 'architect', 'title' => 'Architekt', 'description' => 'Du beginnst jede Stadt mit einem Gebäudeplan.', 'icon' => 'item_bplan_c', 'daysNeeded' => 721, 'items' => ['bplan_c_#00']],
            'prowatch' => ['name' => 'prowatch', 'title' => 'Profiwächter', 'description' => 'Du hast permanent um 5% bessere Chancen auf der Nachtwache.', 'icon' => 'r_guard', 'daysNeeded' => 1000],
        ]);
    }
}