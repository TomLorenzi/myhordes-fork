<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class AwardFeatureDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            'f_wtns'  => [ 'icon' => 'r_ginfec', 'label' => 'Zeuge der großen Verseuchung', 'desc' =>  'Als Opfer der Großen Seuche hast du durch dein gestähltes Immunsystem eine Chance, Infektionen abzuwehren.'],
            'f_arma'  => [ 'icon' => 'r_armag' , 'label' => 'Zeuge des Armageddon',         'desc' =>  'Als Zeuge des Armagedon kannst du selbst aus ausweglosen Situationen fliehen.'],
            'f_glory' => [ 'icon' => 'f_glory' , 'label' => 'Ruhm',                         'desc' =>  'Als Champion kannst du deinen Nachrichten im Forum zusätzliche Authorität verleihen.'],
            'f_cam'   => [ 'icon' => 'f_cam'   , 'label' => 'Kamera aus Vorkriegs-Tagen',   'desc' =>  'Diese nostalgische Knipse aus dem letzten Jahrhundert wirkt, als hätte sie schon Aberhunderten Leuten die Netzhaut verbrannt. Ihr schwacher Blitz könnte dich aus brenzligen Situationen retten, wenn du Zombies damit blendest!'],
            'f_alarm' => [ 'icon' => 'f_alarm' , 'label' => 'Kreischender Wecker',          'desc' =>  'Es gibt morges doch nichts schöner, als einen lauten Wecker, der dein Trommelfell schön stimuliert.'],
            'f_sptkt' => [ 'icon' => 'f_sptkt' , 'label' => 'Eintrittskarte',               'desc' =>  'Hiermit kannst du Städten beitreten, obwohl du die Mindestanzahl an Seelenpunkten noch nicht erreicht hast.'],
            'f_share' => [ 'icon' => 'f_share' , 'label' => 'Freundschaft',                 'desc' =>  'Erlaubt es dir, die Heldenfähigkeit "Freundschaft" einmal pro Stadt zu verwenden. Wird nur beim Verwenden der Heldentat verbraucht.'],
        ]);
    }
}