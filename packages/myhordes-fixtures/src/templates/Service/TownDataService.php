<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class TownDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'small' =>  ['name'=>'small'  ,'label'=>'Kleine Stadt'      ,'preset' => true,  'ranked' => false, 'orderBy' =>  2, 'help' => 'Der Schwierigkeitsgrad ist in dieser Stadt geringer. Deswegen gilt sie nicht für das Saison-Ranking. Die Außenwelt ist durchschnittlich 13x13 Felder groß.'],
            'remote' => ['name'=>'remote' ,'label'=>'Entfernte Regionen','preset' => true,  'ranked' => [1,10,25],  'orderBy' =>  1, 'help' => 'Bei den entfernten Regionen handelt es sich um Städte, die nur von sehr erfahrenen Spielern gespielt werden dürfen (<strong>{splimit} Seelenpunkte</strong>). <p>Diese Städte sind den Veteranen vorbehalten.</p><p>Die entsprechenden Karten der entfernten Regionen sind <strong>weitaus größer</strong> und es gibt mehr zu erforschen.</p>'],
            'panda' =>  ['name'=>'panda'  ,'label'=>'Pandämonium'       ,'preset' => true,  'ranked' => [1, 7,15],  'orderBy' =>  0, 'help' => 'Pandämoniumstädte sind <strong>weitaus schwieriger</strong> als normale Städte! Dementsprechend sind auch die Belohnungen und Gewinnmöglichkeiten besser.<br /><strong>Du benötigst mindestens {splimit} Seelenpunkte</strong>, um in diesen Städten spielen zu dürfen.'],
            'custom' => ['name'=>'custom' ,'label'=>'Private Stadt'     ,'preset' => false, 'ranked' => false, 'orderBy' => -1],
        ]);
    }
}