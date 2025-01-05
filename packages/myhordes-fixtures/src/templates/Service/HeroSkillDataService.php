<?php

namespace MyHordes\Fixtures\Service;

use App\Enum\Configuration\CitizenProperties;
use MyHordes\Fixtures\DTO\HeroicExperience\HeroicExperienceDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HeroSkillDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
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
        $container->add()->name('medicine1')->title('Erfahrener Junkie')->description('Du beginnst jede neue Stadt mit einer Erste Hilfe Tasche in deinem Rucksack.')->icon('item_medic')
            ->unlockAt(361)->legacy(true)->grantsItems(['medic_#00'])->commit();
        $container->add()->name('mayor')->title('Bürgermeister')->description('Du kannst Privatstädte gründen (nach deinem nächsten Tod, auf der Seite "Spielen").')->icon('item_map')
            ->disabled(true)
            ->unlockAt(541)->legacy(true)->commit();
        $container->add()->name('architect')->title('Architekt')->description('Du beginnst jede Stadt mit einem Gebäudeplan.')->icon('item_bplan_c')
            ->unlockAt(721)->legacy(true)->grantsItems(['bplan_c_#00'])->commit();
        $container->add()->name('prowatch')->title('Profiwächter')->description('Du hast permanent um 5% bessere Chancen auf der Nachtwache.')->icon('r_guard')
            ->addCitizenProperty( CitizenProperties::EnableProWatchman, true )
            ->unlockAt(1000)->legacy(true)->commit();

        $container->add()->sort(0)
            ->group('Strategie')->title('Strategie')->legacy(false)
            ->icon('super_s0')->name('super_strategist_0')
            ->bullets([
                          '+10 Verteidigung bei der Nachtwache',
                          'Zugang zum Schwarzen Brett',
                          'Versenden von Nachrichten an alle Bürger',
                          'Konstruktionen empfehlen'
                      ])
            ->addCitizenProperty( CitizenProperties::WatchDefense, 10 )
            ->addCitizenProperty( CitizenProperties::EnableBlackboard, true )
            ->addCitizenProperty( CitizenProperties::EnableGroupMessages, true )
            ->addCitizenProperty( CitizenProperties::EnableBuildingRecommendation, true )
            ->level(0)->unlockAt(0)->commit();
        $container->clone('super_strategist_0')
            ->icon('super_s1')->name('super_strategist_1')
            ->bullets([
                          'Ration Wasser',
                          'Kamera aus Vorkriegstagen (2 Ladungen)',
                          '1 anonymen Foren-Post verfassen'
                      ])
            ->addItemGrant( 'water_#00' )
            ->addItemGrant('photo_2_#00', 'apag', true)
            ->addCitizenProperty( CitizenProperties::AnonymousPostLimit, 1 )
            ->level(1)->unlockAt(0)->commit();
        $container->clone('super_strategist_1')
            ->icon('super_s2')->name('super_strategist_2')
            ->bullets([
                          'Profi-Wächter: Deine Überlebenschancen bei der Nachtwache reduzieren sich langsamer',
                          'Kamera aus Vorkriegstagen (3 Ladungen)',
                          'Unbegrenzt anonyme Foren-Posts verfassen',
                          'Eine zusätzliche Beschwerde pro Tag möglich'
                      ])
            ->addCitizenProperty( CitizenProperties::EnableProWatchman, true )
            ->addItemGrant('photo_3_#00', 'apag', true)
            ->addCitizenProperty( CitizenProperties::AnonymousPostLimit, -1 )
            ->addCitizenProperty( CitizenProperties::ComplaintLimit, 5 )
            ->level(2)->unlockAt(40)->commit();
        $container->clone('super_strategist_2')
            ->icon('super_s3')->name('super_strategist_3')
            ->bullets([
                          '2% Bonus-Überlebenschance auf der Nachtwache',
                          'Unbegrenzt anonyme Nachrichten verfassen',
                          'Kamera aus Vorkriegstagen (4 Ladungen)',
                          'Diebstahl von Bürgern in der Stadt möglich'
                      ])
            ->addCitizenProperty( CitizenProperties::WatchSurvivalBonus, 0.02 )
            ->addCitizenProperty( CitizenProperties::AnonymousMessageLimit, -1 )
            ->addItemGrant('photo_4_#00', 'apag', true)
            ->addCitizenProperty( CitizenProperties::EnableAdvancedTheft, true ) // TODO
            ->level(3)->unlockAt(75)->commit();

        $container->add()->sort(1)
            ->group('Umsicht')->title('Umsicht')->legacy(false)
            ->icon('super_u0')->name('super_university_0')
            ->bullets([
                          'Rettung eines anderen Bürgers (Distanz: 1km)',
                          'Den Tod besiegen (Dehydration & Infektion)',
                          'Manipulieren von Registereinträgen (max. 1)',
                      ])
            ->unlocksAction('hero_generic_rescue')
            ->unlocksAction('hero_generic_immune')
            ->addCitizenProperty(CitizenProperties::HeroRescueRange, 1)
            ->addCitizenProperty(CitizenProperties::LogManipulationLimit, 1)
            ->addCitizenProperty(CitizenProperties::HeroImmuneStatusList, ['thirst','infection'])
            ->addCitizenProperty(CitizenProperties::StatusOverrideMap, ['hsurvive2' => 'hsurvive2_l0'])
            ->level(0)->unlockAt(0)->commit();
        $container->clone('super_university_0')
            ->icon('super_u1')->name('super_university_1')
            ->bullets([
                          'Rettung eines anderen Bürgers (Distanz: 2km)',
                          'Den Tod besiegen (zusätzlicher Schutz vor Drogenentzug)',
                          'Manipulieren von Registereinträgen (max. 2)',
                      ])
            ->addCitizenProperty(CitizenProperties::HeroRescueRange, 2)
            ->addCitizenProperty(CitizenProperties::LogManipulationLimit, 2)
            ->addCitizenProperty(CitizenProperties::HeroImmuneStatusList, ['thirst','infection','addiction'])
            ->addCitizenProperty(CitizenProperties::StatusOverrideMap, ['hsurvive2' => 'hsurvive2_l1'])
            ->level(1)->unlockAt(0)->commit();
        $container->clone('super_university_1')
            ->icon('super_u2')->name('super_university_2')
            ->bullets([
                          'Rettung eines anderen Bürgers (Distanz: 3km)',
                          'Den Tod besiegen (zusätzlicher Schutz vor Hungertod als Ghul)',
                          'Manipulieren von Registereinträgen (max. 3)',
                          'Ein zusätzlicher Platz in der Truhe',
                      ])
            ->addCitizenProperty(CitizenProperties::HeroRescueRange, 3)
            ->addCitizenProperty(CitizenProperties::LogManipulationLimit, 3)
            ->addCitizenProperty(CitizenProperties::ChestSpaceBonus, 1)
            ->addCitizenProperty(CitizenProperties::HeroImmuneStatusList, ['thirst','infection','addiction','hunger'])
            ->addCitizenProperty(CitizenProperties::StatusOverrideMap, ['hsurvive2' => 'hsurvive2_l2'])
            ->level(2)->unlockAt(40)->commit();
        $container->clone('super_university_2')
            ->icon('super_u3')->name('super_university_3')
            ->bullets([
                          'Den Tod besiegen (heilt Kater, Bandagiert und Angst)',
                          'Rudimentäre Pflege',
                          'Löschen von Registereinträgen (max. 1)',
                      ])
            ->addCitizenProperty(CitizenProperties::HeroImmuneHeals, true)
            ->addCitizenProperty(CitizenProperties::LogPurgeLimit, 1)
            ->addCitizenProperty(CitizenProperties::StatusOverrideMap, ['hsurvive2' => 'hsurvive2_l3'])
            ->unlocksAction('hero_generic_immune2')
            ->level(3)->unlockAt(75)->commit();

        $container->add()->sort(2)
            ->group('Planung')->title('Planung')->legacy(false)
            ->icon('super_p0')->name('super_preparation_0')
            ->bullets([
                          '1 zusätzlicher Zonenkontrollpunkt wenn "clean"',
                          'Hellseherei: Du erfährst, wie aktiv ein bestimmter Bürger in deiner Stadt spielt.',
                          'Doggybag'
                      ])
            ->addCitizenProperty( CitizenProperties::ZoneControlCleanBonus, 1 )
            ->addCitizenProperty( CitizenProperties::EnableClairvoyance, true )
            ->addItemGrant('food_bag_#00', 'doggybag')
            ->level(0)->unlockAt(0)->commit();
        $container->clone('super_preparation_0')
            ->icon('super_p1')->name('super_preparation_1')
            ->bullets([
                          '1 zusätzlicher Zonenkontrollpunkt',
                          'Allwissenheit: Wie Hellseherei, außer, dass du jetzt eine Übersicht zur Aktivität aller Stadteinwohner bekommst',
                          'Vorräte eines umsichtigen Bürgers',
                          'Erste Hilfe Tasche'
                      ])
            ->addCitizenProperty( CitizenProperties::ZoneControlBonus, 1 )
            ->addCitizenProperty( CitizenProperties::EnableOmniscience, true )
            ->addItemGrant('chest_hero_#00')
            ->addItemGrant('medic_#00')
            ->level(1)->unlockAt(0)->commit();
        $container->clone('super_preparation_1')
            ->icon('super_p2')->name('super_preparation_2')
            ->bullets([
                          '1 zusätzlicher Zonenkontrollpunkt wenn nicht "durstig" oder "dehydriert"',
                          'Gewöhnlicher Bauplan',
                          'Lunchbox (statt Doggybag)',
                      ])
            ->addCitizenProperty( CitizenProperties::ZoneControlHydratedBonus, 1 )
            ->addItemGrant('bplan_c_#00', 'plan')
            ->addItemGrant('food_armag_#00', 'doggybag')
            ->level(2)->unlockAt(40)->commit();
        $container->clone('super_preparation_2')
            ->icon('super_p3')->name('super_preparation_3')
            ->bullets([
                          '1 zusätzlicher Zonenkontrollpunkt wenn nicht "betrunken" oder "verkatert"',
                          'Abgenutzte Kuriertasche (statt Bauplan) beim Start der Stadt',
                          'Cellokasten',
                      ])
            ->addCitizenProperty( CitizenProperties::ZoneControlSoberBonus, 1 )
            ->addItemGrant('bplan_drop_#00', 'plan')
            ->addItemGrant('cello_box_#00')
            ->level(3)->unlockAt(75)->commit();

        $container->add()->sort(3)
            ->group('Eifer')->title('Eifer')->legacy(false)
            ->icon('super_e0')->name('super_enduring_0')
            ->bullets([
                          'Ein zusätzlicher Platz im Rucksack',
                          'Ein zusätzlicher Platz in der Truhe',
                          'Wildstyle Uppercut',
                          'Zweite Lunge (4 EP)',
                      ])
            ->addCitizenProperty(CitizenProperties::InventorySpaceBonus, 1)
            ->addCitizenProperty(CitizenProperties::ChestSpaceBonus, 1)
            ->addCitizenProperty(CitizenProperties::HeroSecondWindBaseSP, 4)
            ->unlocksAction('hero_generic_punch')
            ->unlocksAction('hero_generic_ap')
            ->level(0)->unlockAt(0)->commit();
        $container->clone('super_enduring_0')
            ->icon('super_e1')->name('super_enduring_1')
            ->bullets([
                          'Ein weiterer zusätzlicher Platz im Rucksack',
                          'Ein weiterer zusätzlicher Platz in der Truhe',
                          'Verbesserung Wildstyle Uppercut (3 Zombies)',
                          'Verbesserung Zweite Lunge (6 EP)',
                      ])
            ->addCitizenProperty(CitizenProperties::InventorySpaceBonus, 2)
            ->addCitizenProperty(CitizenProperties::ChestSpaceBonus, 2)
            ->addCitizenProperty( CitizenProperties::HeroPunchKills, 3 )
            ->addCitizenProperty(CitizenProperties::HeroSecondWindBaseSP, 6)
            ->level(1)->unlockAt(0)->commit();
        $container->clone('super_enduring_1')
            ->icon('super_e2')->name('super_enduring_2')
            ->bullets([
                          '1 Gegenstand in der Truhe ist versteckt',
                          'Verbesserung Wildstyle Uppercut (4 Zombies)',
                          'Verbesserung Zweite Lunge (2 AP, 6 EP)',
                          '15 Extra-O² in begehbaren Ruinen',
                      ])
            ->addCitizenProperty( CitizenProperties::ChestHiddenStashLimit, 1 )
            ->addCitizenProperty( CitizenProperties::HeroPunchKills, 4 )
            ->addCitizenProperty( CitizenProperties::OxygenTimeBonus, 45 )
            ->addCitizenProperty(CitizenProperties::HeroSecondWindBonusAP, 2)
            ->level(2)->unlockAt(40)->commit();
        $container->clone('super_enduring_2')
            ->icon('super_e3')->name('super_enduring_3')
            ->bullets([
                          'Ein weiterer zusätzlicher Platz im Rucksack',
                          '30 Sekunden temporäre Zonencontrolle durch Wildstyle Uppercut',
                          'Verbesserung Zweite Lunge (4 AP, 6 EP)',
                          '30 Extra-O² in begehbaren Ruinen',
                      ])
            ->addCitizenProperty(CitizenProperties::InventorySpaceBonus, 3)
            ->addCitizenProperty(CitizenProperties::HeroPunchEscapeTime, 30)
            ->addCitizenProperty(CitizenProperties::OxygenTimeBonus, 90)
            ->addCitizenProperty(CitizenProperties::HeroSecondWindBaseSP, 6)
            ->addCitizenProperty(CitizenProperties::HeroSecondWindBonusAP, 4)
            ->level(3)->unlockAt(75)->commit();

        $container->add()->sort(4)
            ->group('Ruhe')->title('Ruhe')->legacy(false)
            ->icon('super_r0')->name('super_recluse0')
            ->bullets([
                          'Rückkehr des Helden (9km)',
                          'Proficamper (auf 6 Campings begrenzt)',
                          'Fund',
                      ])
            ->unlocksAction('hero_generic_return')
            ->unlocksAction('hero_generic_find', 'find')
            ->addCitizenProperty( CitizenProperties::EnableProCamper, true )
            ->addCitizenProperty( CitizenProperties::ProCamperUsageLimit, 6 )
            ->level(0)->unlockAt(0)->commit();
        $container->clone('super_recluse0')
            ->icon('super_r1')->name('super_recluse1')
            ->bullets([
                          'Rückkehr des Helden (11km)',
                          'Proficamper (auf 8 Campings begrenzt)',
                          'Schönes Fundstück (ersetzt Fund)',
                          'Solltest du am dritten Tag oder an einem späteren Zeitpunkt verbannt werden, bekommst du automatisch etwas Gift geschenkt, das du nach Belieben einsetzen kannst... Tja, man hätte dich besser nicht ärgern sollen!',
                      ])
            ->unlocksAction('hero_generic_find_lucky', 'find')
            ->addCitizenProperty(CitizenProperties::HeroReturnRange, 11)
            ->addCitizenProperty( CitizenProperties::RevengeItems, ['poison_#00','poison_#00'] )
            ->addCitizenProperty( CitizenProperties::ProCamperUsageLimit, 8 )
            ->level(1)->unlockAt(0)->commit();
        $container->clone('super_recluse1')
            ->icon('super_r2')->name('super_recluse2')
            ->bullets([
                          'Rückkehr des Helden (13km)',
                          'Beeindruckendes Fundstück (ersetzt Schönes Fundstück)',
                          'Solltest du am dritten Tag oder an einem späteren Zeitpunkt verbannt werden, bekommst du automatisch etwas Toxin geschenkt, das du nach Belieben einsetzen kannst... Tja, man hätte dich besser nicht ärgern sollen!',
                          '1 zusätzliche Mülldurchwühlung pro Tag',
                      ])
            ->addCitizenProperty(CitizenProperties::HeroReturnRange, 13)
            ->addCitizenProperty( CitizenProperties::RevengeItems, ['poison_#00','poison_#00', 'infect_poison_#00'] )
            ->addCitizenProperty( CitizenProperties::TrashSearchLimit, 4 )
            ->unlocksAction('hero_generic_find_lcky2', 'find')
            ->level(2)->unlockAt(40)->commit();
        $container->clone('super_recluse2')
            ->icon('super_r3')->name('super_recluse3')
            ->bullets([
                          'Rückkehr des Helden (15km)',
                          'Ermöglicht Überlebenschance beim Camping von bis zu 99%',
                          'Erstaunliches Fundstück (ersetzt Beeindruckendes Fundstück)',
                      ])
            ->addCitizenProperty(CitizenProperties::HeroReturnRange, 15)
            ->addCitizenProperty(CitizenProperties::CampingChanceCap, 0.99)
            ->unlocksAction('hero_generic_find_lcky3', 'find')
            ->level(3)->unlockAt(75)->commit();

        $data = $container->toArray();
    }
}