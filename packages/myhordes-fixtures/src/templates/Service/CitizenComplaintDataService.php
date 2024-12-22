<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenComplaintDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            ['name' => 'theft', 'text' => 'Zahlreiche Diebstähle begangen'],
            ['name' => 'water', 'text' => 'Verbraucht zuviel Wasser'],
            ['name' => 'insulting', 'text' => 'Beleidigendes Verhalten'],
            ['name' => 'buildings', 'text' => 'Blockiert die Baustelle'],
            ['name' => 'expeditions', 'text' => 'Expeditionssaboteur'],
            ['name' => 'wimp', 'text' => 'Geht kein Risiko ein'],
            ['name' => 'selfish', 'text' => 'Handelt zu egoistisch'],
            ['name' => 'communautary', 'text' => 'Gemeinschaftsfreak'],
            ['name' => 'noinvolvment', 'text' => 'Bringt sich nicht genug ein'],
            ['name' => 'toomanyitems', 'text' => 'Hortet zu viele Gegenstände'],
            ['name' => 'violent', 'text' => 'Aggressiver Mitbürger'],
        ]);
    }
}