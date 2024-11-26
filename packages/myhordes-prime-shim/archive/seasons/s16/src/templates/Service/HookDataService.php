<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class HookDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_replace_recursive($data, [
            'dumpDisplayCostCore' => [
				'active' => false,
			],
			'dumpDisplayCostPrime' => [
				'hookname' => 'dumpDisplayCost',
				'classname' => "MyHordes\\Prime\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 1
			],
			'dumpDisplayItemsCore' => [
				'active' => false,
			],
			'dumpDisplayActionsCoreJs' => [
				'active' => false,
			],
            'additionalCitizenRowClassPrime' => [
                'hookname' => 'additionalCitizenRowClass',
                'classname' => "MyHordes\\Prime\\Hooks\\ClassHooks",
                'active' => true,
                'position' => 1
            ],
			'dumpDisplayItemsPrime' => [
				'hookname' => 'dumpDisplayItems',
				'classname' => "MyHordes\\Prime\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 1
			],
			'dumpDisplayActionsPrimeJs' => [
				'hookname' => 'dumpDisplayActionsJs',
				'classname' => "MyHordes\\Prime\\Hooks\\DumpHooks",
				'active' => true,
				'position' => 1
			],
			'nightwatchHeaderPrime' => [
				'hookname' => 'nightwatchHeader',
				'classname' => 'MyHordes\\Prime\\Hooks\\NightwatchHooks',
				'active' => true,
				'position' => 1
			],
			'homeForeignDeadActionsPrime' => [
				'hookname' => 'homeForeignDeadActions',
				'classname' => 'MyHordes\\Prime\\Hooks\\TownHooks',
				'active' => true,
				'position' => 1
			],
			'homeForeignDeadActionsPrimeJs' => [
				'hookname' => 'homeForeignDeadActionsJs',
				'classname' => 'MyHordes\\Prime\\Hooks\\TownHooks',
				'active' => true,
				'position' => 1
			],
			'homeForeignDisposalTextPrime' => [
				'hookname' => 'homeForeignDisposalText',
				'classname' => 'MyHordes\\Prime\\Hooks\\TownHooks',
				'active' => true,
				'position' => 1
			],
			'WorkshopTechTextPrime' => [
				'hookname' => 'WorkShopTech',
				'classname' => 'MyHordes\\Prime\\Hooks\\WorkshopHooks',
				'active' => true,
				'position' => 1
			]
        ]);
    }
}