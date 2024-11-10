<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HeroSkillDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
			'medicine1' => ['description' => 'Du beginnst jede neue Stadt mit einer Erste Hilfe Tasche in deinem Rucksack.', 'items' => ['medic_#00']],
        ]);
    }
}