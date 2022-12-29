<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenNotificationMarkerDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            'ghoul',
            'shaman',
            'guide',
            'insurrection',
            'stranger'
        ]);
    }
}