<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CitizenNotificationMarkerDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'ghoul',
            'shaman',
            'guide',
            'insurrection',
            'stranger',
            'altar'
        ]);
    }
}