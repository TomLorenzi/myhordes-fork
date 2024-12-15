<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenStatusDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            'immune' 	=> ['name' => 'immune',                     'nw_death' => -0.01,  'label' => 'Immunisiert', 		'description' => 'Du hast Medizin eingenommen, die dich vor Infektionen schützt und dich davor bewahrt, zu einem Ghul zu werden.'],
            'terror' 	=> ['name' => 'terror',    'nw_def' => -30, 'nw_death' =>  0.05,  'label' => 'Angststarre', 		'description' => 'Dir ist etwas furchtbares wiederfahren, und du bist vor Angst erstarrt! Du kannst dich nicht länger in einer von Zombies kontrollierten Zone aufhalten. Wenn du gefangen bist, kannst du nicht länger fliehen.'],
            'thirst1' 	=> ['name' => 'thirst1',   'nw_def' => -5,                        'label' => 'Durst', 				'description' => 'Du bist durstig... Das passiert immer dann wenn du am Vortag nichts getrunken hast oder wenn du in der Wüste lange Strecken gelaufen bist...'],
            'thirst2' 	=> ['name' => 'thirst2',   'nw_def' => -10, 'nw_death' =>  0.03,  'label' => 'Dehydriert', 			'description' => 'Dein Durst hat ein kritisches Level erreicht! Trinke schnell etwas, oder du riskierst zu sterben!'],
            'drugged' 	=> ['name' => 'drugged',   'nw_def' =>  10,                       'label' => 'Rauschzustand',	 	'description' => 'Du hast heute bereits Drogen konsumiert. Wenn du noch weitere Drogen nimmst, riskierst du eine Abhängigkeit!'],
            'addict' 	=> ['name' => 'addict',    'nw_def' =>  10, 'nw_death' =>  0.06,  'label' => 'Drogenabhängig',	 	'description' => 'Du musst jeden Tag Drogen einnehmen! Wenn du eines morgens aufwachst, ohne am Tag zuvor Drogen genommen zu haben, wirst du sterben!'],
            'infection' => ['name' => 'infection', 'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Infektion', 			'description' => 'Eine furchtbare Krankheit brennt sich durch dein Innerstes... Vielleicht eine Art Infektion? Das beste, was du jetzt tun kannst, ist die richtige Medizin einzunehmen... Wenn du hingegen nichts tust, hast du eine 50/50 Chance, morgen tot aufzuwachen.'],
            'drunk' 	=> ['name' => 'drunk',     'nw_def' =>  15, 'nw_death' => -0.02,  'label' => 'Trunkenheit', 		'description' => 'Du stehst unter dem Einfluss von ziemlich starkem Alkohol... Du kannst vorerst keinen weiteren Alkohol zu dir nehmen.'],
            'hungover' 	=> ['name' => 'hungover',  'nw_def' => -15, 'nw_death' =>  0.06,  'label' => 'Kater', 				'description' => 'Du hast furchtbare Kopfschmerzen... Keinesfalls kannst du heute weiteren Alkohol zu dir nehmen.'],
            'wound1' 	=> ['name' => 'wound1',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Kopf', 	'description' => 'Du bist am Kopf verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'wound2' 	=> ['name' => 'wound2',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Hände', 	'description' => 'Du bist an der Hand verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'wound3' 	=> ['name' => 'wound3',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Arme', 	'description' => 'Du bist an deinem Arm verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'wound4' 	=> ['name' => 'wound4',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Bein', 	'description' => 'Du bist an deinen Beinen verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'wound5' 	=> ['name' => 'wound5',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Auge', 	'description' => 'Du bist an den Augen verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'wound6' 	=> ['name' => 'wound6',    'nw_def' => -15, 'nw_death' =>  0.10,  'label' => 'Verwundung - Fuß', 	'description' => 'Du bist am Fuß verletzt! Essen, trinken und Ausruhen wird dir 1AP weniger verschaffen.'],
            'healed'	=> ['name' => 'healed',    'nw_def' => -15, 'nw_death' =>  0.05,  'label' => 'Bandagiert', 			'description' => 'Du hast dich bereits von einer Verletzung erholt. Du kannst heute nicht erneut geheilt werden.'],

			'tg_home_pool'  => ['name' => 'tg_home_pool',  'volatile' => true],
			'tg_rec_heroic' => ['name' => 'tg_rec_heroic', 'volatile' => true],

            'tg_got_xmas1' => ['name' => 'tg_got_xmas1', 'volatile' => false],
            'tg_got_xmas2' => ['name' => 'tg_got_xmas2', 'volatile' => false],
            'tg_got_xmas3' => ['name' => 'tg_got_xmas3', 'volatile' => false],
        ]);
    }
}