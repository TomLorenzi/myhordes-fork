<?php

namespace MyHordes\Fixtures\Service;

use App\Enum\Configuration\CitizenProperties;
use MyHordes\Fixtures\DTO\HeroicExperience\HeroicExperienceDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HeroSkillDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $container = new HeroicExperienceDataContainer();
        $container->add()->name('manipulator')->title('Tipp-Ex')->description('Du kannst 2 Mal pro Partie einen Registereintrag unkenntlich machen. Dazu musst du nur auf das kleine Icon "Fälschen" klicken. Dieses befindet sich links neben dem "störenden" Registereintrag. ;-)')->icon('small_falsify')
            ->addCitizenProperty( CitizenProperties::LogManipulationLimit, 2 )
            ->unlockAt(3)->legacy(true)->commit();
        $container->add()->name('clairvoyance')->title('Hellseherei')->description('Du erfährst, wie aktiv ein bestimmter Bürger in deiner Stadt spielt. Du musst dazu lediglich bei ihm daheim vorbeischauen...')->icon('small_view')
            ->addCitizenProperty( CitizenProperties::EnableClairvoyance, true )
            ->unlockAt(7)->legacy(true)->commit();
        $container->add()->name('writer')->title('Rundbrief')->description('Mit dieser Funktion kannst du alle Stadteinwohner auf einmal anschreiben. Du kannst damit in den Foren zudem Rollenspielthreads (RP) erstellen (Übertreibe es aber bitte nicht - Danke!).')->icon('item_rp_sheets')
            ->addCitizenProperty( CitizenProperties::EnableGroupMessages, true )
            ->unlockAt(16)->legacy(true)->commit();
        $container->add()->name('brothers')->title('Freundschaft')->description('Mit dieser Fähigkeit kannst du 1 Mal pro Partie (Stadt) jemandem eine deiner noch ungenutzten Heldentaten schenken! Die anderen werden in dir den Messias erkennen! BEACHTE: Verschenkte Heldentaten kannst du in dieser Stadt nicht mehr selbst verwenden. Um jemandem eine Heldentat zu schenken, musst mit ihm in der gleichen Zone stehen oder, wenn du in der Stadt bist, ihn daheim besuchen.')->icon('r_share')
            ->disabled(true)
            ->unlockAt(25)->legacy(true)->commit();
        $container->add()->name('dictator')->title('Diktator')->description('Schreibe auf die Tafel, die sich auf der Übersichtsseite deiner Stadt befindet. Darüberhinaus kannst du auch deinen Mitbürgern ein Bauprojekt empfehlen. Das Gebäude mit den meisten Empfehlungen wird auf der Bauseite der Stadt hervorgehoben.')->icon('small_chat')
            ->addCitizenProperty(CitizenProperties::EnableBlackboard, true)
            ->addCitizenProperty(CitizenProperties::EnableBuildingRecommendation, true)
            ->unlockAt(31)->legacy(true)->commit();
        $container->add()->name('largechest1')->title('Große Truhe')->description('Du erhältst einen zusätzlichen Platz in deiner Truhe.')->icon('item_home_box')
            ->unlockAt(45)->legacy(true)->chestSpace(1)->commit();
        $container->add()->name('healthybody')->title('Top in Form')->description('Solange du "Clean" bist (sprich: Du hast in der Partie noch keine Drogen zu Dir genommen.), verfügst du über einen zusätzlichen Zonenkontrollpunkt in der Außenwelt.')->icon('status_clean')
            ->addCitizenProperty( CitizenProperties::ZoneControlCleanBonus, 1 )
            ->unlockAt(61)->legacy(true)->commit();
        $container->add()->name('omniscience')->title('Allwissenheit')->description('Allwissenheit funktioniert wie Hellseherei, außer, dass du jetzt eine Übersicht zur Aktivität aller Stadteinwohner bekommst. Klicke dazu in der Bürgerliste einfach auf den Button "Allwissenheit".')->icon('small_view')
            ->addCitizenProperty( CitizenProperties::EnableOmniscience, true )
            ->unlockAt(75)->legacy(true)->commit();
        $container->add()->name('resourcefulness')->title('Einfallsreichtum')->description('Du beginnst jede neue Stadt mit einem zusätzlichen nützlichen Gegenstand.')->icon('item_chest_hero')
            ->unlockAt(91)->legacy(true)->grantsItems(['chest_hero_#00'])->commit();
        $container->add()->name('largechest2')->title('Doppelter Boden')->description('Du bekommst einen weiteren Platz in deiner Truhe.')->icon('item_home_box')
            ->unlockAt(105)->legacy(true)->chestSpace(1)->commit();
        $container->add()->name('luckyfind')->title('Schönes Fundstück')->description('Mit deiner Heldenfähigkeit "Fundstück" stöberst du jetzt NOCH BESSERE Gegenstände auf.')->icon('item_chest_hero')
            ->unlockAt(121)->legacy(true)->unlocksAction('hero_generic_find_lucky')->commit();
        $container->add()->name('largerucksack1')->title('Aufgeräumte Tasche')->description('Du bekommst einen zusätzlichen Platz in deinem Rucksack.')->icon('item_bag')
            ->addCitizenProperty( CitizenProperties::InventorySpaceBonus, 1 )
            ->unlockAt(135)->legacy(true)->commit();
        $container->add()->name('secondwind')->title('Zweite Lunge')->description('Du verfügst ab sofort über eine mächtige Heldenfähigkeit, mit der du 6 AP wiederherstellen kannst und die deine Müdigkeit aufhebt.')->icon('small_pa')
            ->unlockAt(151)->legacy(true)->unlocksAction('hero_generic_ap')->addCitizenProperty( CitizenProperties::HeroSecondWindBonusAP, 6 )->commit();
        $container->add()->name('breakfast1')->title('Weitsichtig')->description('Du beginnst jede neue Stadt mit einer zusätzlichen Nahrungsmittelration.')->icon('item_food_bag')
            ->unlockAt(165)->legacy(true)->grantsItems(['food_bag_#00'])->commit();
        $container->add()->name('apag')->title('Profi-Fotograph')->description('Du beginst jede neue Stadt mit einer Kamera aus Vorkriegstagen.')->icon('f_cam')
            ->unlockAt(181)->legacy(true)->grantsItems(['photo_3_#00'])->itemsGrantedAsProfessionItems(true)->inhibitedByFeatureUnlock('f_cam')->commit();
        $container->add()->name('brick')->title('Panzerschrank')->description('Dank deiner zahlreichen Zombiekontakte verfügst du in der Außenwelt ab sofort über einen zusätzlichen Zonenkontrollpunkt.')->icon('item_shield')
            ->addCitizenProperty( CitizenProperties::ZoneControlBonus, 1 )
            ->unlockAt(195)->legacy(true)->commit();
        $container->add()->name('treachery')->title('Hinterhältigkeit')->description('Die "Tipp-Ex" Fähigkeit wird noch besser! Ab sofort kannst du pro Partie (Stadt) noch mehr Registereinträge fälschen! Du kannst ab sofort 4 Mal pro Partie einen Registereintrag unkenntlich machen.')->icon('small_falsify')
            ->addCitizenProperty( CitizenProperties::LogManipulationLimit, 4 )
            ->unlockAt(211)->legacy(true)->commit();
        $container->add()->name('cheatdeath')->title('Den Tod besiegen')->description('Sobald diese neue Heldenfähigkeit aktiviert wurde, spürst du beim nächsten Zombieangriff keinen Durst, keine Drogenabhängigkeit und keine Infektion (nur eine Nacht lang gültig). Verhindert auch Ghulverhungern (Hungerbalken steigt trotzdem).')->icon('small_wrestle')
            ->unlockAt(226)->legacy(true)
            ->unlocksAction('hero_generic_immune')
            ->addCitizenProperty(CitizenProperties::HeroImmuneStatusList, ['thirst','infection','addiction','hunger'])
            ->commit();
        $container->add()->name('revenge')->title('Süße Rache')->description('Solltest du am dritten Tag oder an einem späteren Zeitpunkt verbannt werden, bekommst du automatisch etwas Gift geschenkt, das du nach Belieben einsetzen kannst... Tja, man hätte dich besser nicht ärgern sollen!')->icon('item_april_drug')
            ->addCitizenProperty( CitizenProperties::RevengeItems, ['poison_#00','poison_#00'] )
            ->unlockAt(241)->legacy(true)->commit();
        $container->add()->name('procamp')->title('Proficamper')->description('Die Nachteile, die bei wiederholtem Campen auftreten, fallen bei dir nicht mehr so stark aus: Somit kannst du in einer Stadt öfter campen.')->icon('small_camp')
            ->addCitizenProperty( CitizenProperties::EnableProCamper, true )
            ->unlockAt(301)->legacy(true)->commit();
        $container->add()->name('medicine1')->title('Erfahrener Junkie')->description('Du beginnst jede neue Stadt mit einer Ration Paracetoid 7g in deinem Rucksack.')->icon('item_disinfect')
            ->unlockAt(361)->legacy(true)->grantsItems(['disinfect_#00'])->commit();
        $container->add()->name('mayor')->title('Bürgermeister')->description('Du kannst Privatstädte gründen (nach deinem nächsten Tod, auf der Seite "Spielen").')->icon('item_map')
            ->disabled(true)
            ->unlockAt(541)->legacy(true)->commit();
        $container->add()->name('architect')->title('Architekt')->description('Du beginnst jede Stadt mit einem Gebäudeplan.')->icon('item_bplan_c')
            ->unlockAt(721)->legacy(true)->grantsItems(['bplan_c_#00'])->commit();
        $container->add()->name('prowatch')->title('Profiwächter')->description('Du hast permanent um 5% bessere Chancen auf der Nachtwache.')->icon('r_guard')
            ->addCitizenProperty( CitizenProperties::EnableProWatchman, true )
            ->unlockAt(1000)->legacy(true)->commit();

        $data = $container->toArray();
    }
}