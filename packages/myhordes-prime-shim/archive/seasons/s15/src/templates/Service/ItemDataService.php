<?php

namespace MyHordes\Prime\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $overwrite = [
            'fest'          => [ 'label' => 'Abgestandenes Bier' ],
            'tekel'         => [ 'label' => 'Räudiger Dackel' ],
            'cinema'        => [ 'label' => 'Antiker Videoprojektor' ],
            'bretz'         => [ 'label' => 'Sandige Bretzel' ],
            'vodka_de'      => [ 'label' => 'Grüne Bierflasche (prime)' ],
            'hurling_stick' => [ 'label' => 'Primitiver Hurlingstock', 'watchpoint' => 15 ],
            'guiness'       => [ 'label' => 'Klebriges Pint' ],
            'badge'         => [ 'label' => 'Rostiges Abzeichen', 'watchpoint' => 30 ],

            'distri'  => [ 'heavy' => true ],
            'pc'      => [ 'watchpoint' => 12 ],
            'guitar'  => [ 'watchpoint' => 18 ],
            'trestle' => [ 'watchpoint' =>  5 ],
            'watergun_1'   => [ 'watchpoint' => 4 ],
            'watergun_3'   => [ 'watchpoint' => 12 ],
            'watergun_opt_1' => [ 'watchpoint' => 4 ],
            'watergun_opt_3' => [ 'watchpoint' => 12 ],
            'watergun_opt_4' => [ 'watchpoint' => 16 ],
            'watergun_opt_5' => [ 'watchpoint' => 20 ],
        ];

        foreach (($data['items'] ?? []) as $key => $entry) {
            $o = $overwrite[ $entry['icon'] ?? '' ] ?? [];
            $data['items'][$key] = array_merge( $entry, $o );
        }

        // This is used for translation where there is no input data
        if (!isset($data['items'])) $data['items'] = [];
        foreach ($overwrite as $icon => $mask)
            if (empty( array_filter( $data['items'], fn(array $e) => ($e['icon'] ?? null) === $icon ) ))
                $data['items'][] = $mask;

        $data = array_merge_recursive($data, [
            'items' => [

            ],

            'descriptions' => [

            ],
        ]);
    }
}