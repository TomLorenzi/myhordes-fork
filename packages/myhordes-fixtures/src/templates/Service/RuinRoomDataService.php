<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class RuinRoomDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            [
                "label" => "Offene Tür"
            ],
            [
                "label" => "Verschlossene Tür (Flaschenöffner)",
                "lock_mold" => 'prints_#02',
                "lock_mold_alt" => 'noodle_prints_#02',
                "lock_item" => 'classicKey_#00',
            ],
            [
                "label" => "Verschlossene Tür (Schlagschlüssel)",
                "lock_mold" => 'prints_#01',
                "lock_mold_alt" => 'noodle_prints_#01',
                "lock_item" => 'bumpKey_#00',
            ],
            [
                "label" => "Verschlossene Tür (Magnetschlüssel)",
                "lock_mold" => 'prints_#00',
                "lock_mold_alt" => 'noodle_prints_#00',
                "lock_item" => 'magneticKey_#00',
            ],
            [
                "label" => "Treppenaufgang",
                "level" => 1
            ],
            [
                "label" => "Treppenabstieg",
                "level" => -1
            ],
        ]);
    }
}