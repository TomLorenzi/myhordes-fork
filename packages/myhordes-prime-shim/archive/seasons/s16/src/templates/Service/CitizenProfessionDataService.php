<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenProfessionDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            'guardian' => ['hero' => true,  'icon' => 'shield', 'name'=>'guardian',    'desc' => 'Der Wächter ist der geborener Kämpfer. In der Wüste kann er es mit weitaus mehr Zombies aufnehmen als jeder andere durchschnittliche Einwohner.', 'label'=>'Wächter',    'items' => ['basic_suit_#00','shield_#00'],     'items_alt' => ['basic_suit_dirt_#00'] , 'picto' => 'r_jguard_#00', 'nightwatch_def_bonus' => 20, 'nightwatch_surv_bonus' => 0.05 ],
            'tamer' => ['hero' => true,  'icon' => 'tamer',  'name'=>'tamer',       'desc' => 'Der Dompteur setzt seinen treuen Hund dazu ein, um in der Wüste gefundene Gegenstände in die Stadt zu bringen.', 'label'=>'Dompteur',   'items' => ['basic_suit_#00','tamed_pet_#00'],  'items_alt' => ['basic_suit_dirt_#00','tamed_pet_drug_#00','tamed_pet_off_#00'] , 'picto' => 'r_jtamer_#00', 'nightwatch_def_bonus' => 5, 'nightwatch_surv_bonus' => 0 ],
        ]);
    }
}