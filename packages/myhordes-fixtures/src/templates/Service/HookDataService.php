<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HookDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'dumpDisplayCostCore' => [
				'hookname' => 'dumpDisplayCost',
				'classname' => "App\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 0
			],
            'additionalCitizenRowClassCore' => [
                'hookname' => 'additionalCitizenRowClass',
                'classname' => "App\\Hooks\\ClassHooks",
                'active' => true,
                'position' => 0
            ],
			'dumpDisplayItemsCore' => [
				'hookname' => 'dumpDisplayItems',
				'classname' => "App\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 0
			],
			'dumpDisplayActionsCoreJs' => [
				'hookname' => 'dumpDisplayActionsJs',
				'classname' => "App\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 0
			]
        ]);
    }
}