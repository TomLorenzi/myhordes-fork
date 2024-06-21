<?php

namespace MyHordes\Prime\Service;

use MyHordes\Fixtures\DTO\Awards\AwardTitlePrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class AwardTitleDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $container = new AwardTitlePrototypeDataContainer($data);

        // Resident titles
        $container->add()->title('Keine Titel zum Freischalten')->unlockquantity(10)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Selbst wenn es kostenlos ist - ich zahle trotzdem nicht!')->unlockquantity(25)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Muggel')->unlockquantity(75)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Otto Normalverbraucher')->unlockquantity(150)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Stärke: Zu schwach.')->unlockquantity(300)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Proletarier aller Wüsten, vereinigt euch!')->unlockquantity(800)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Was? Warum hat mir das niemand vorher gesagt?')->unlockquantity(2000)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->title('Festliche Kleidung wird vorausgesetzt.')->unlockquantity(2500)->associatedtag(':probasic:')->associatedpicto('r_jbasic_#00')->commit();

        // Additional camper titles
        $container->add()->title('Ich bin nicht da')->unlockquantity(400)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('Nur ich und die Sterne ...')->unlockquantity(450)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('Immun gegen Mücken')->unlockquantity(500)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('In der Stadt schlafen? Warum?')->unlockquantity(600)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('Abenteuer Freiheit')->unlockquantity(700)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('Besser allein als schlecht begleitet')->unlockquantity(800)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('Obdachloser')->unlockquantity(900)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();
        $container->add()->title('König der Wüste')->unlockquantity(1000)->associatedtag(':camper:')->associatedpicto('r_camp_#00')->commit();

        // Additional clean titles
        $container->add()->title('Breaking Good')->unlockquantity(1500)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Zu sauber, um ehrlich zu sein')->unlockquantity(2500)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Ist das auch wirklich bio?')->unlockquantity(3333)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Antivax')->unlockquantity(4500)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Drogen sind schlecht, m\'kay')->unlockquantity(6666)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Süchtig nach meinem Kontrollpunkt')->unlockquantity(8500)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Nobelpreis für Gesundheit')->unlockquantity(10000)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->title('Wenn Zombies es nicht tun... Warum sollte ich es tun?')->unlockquantity(15000)->associatedtag(':clean:')->associatedpicto('r_nodrug_#00')->commit();

        $data = $container->toArray();
    }
}