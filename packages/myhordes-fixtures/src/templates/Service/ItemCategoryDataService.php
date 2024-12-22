<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ItemCategoryDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            ["name" => "Rsc", "label" => "Baustoffe", "parent" => null, "ordering" => 0],
            ["name" => "Furniture", "label" => "Einrichtungen", "parent" => null, "ordering" => 1],
            ["name" => "Weapon", "label" => "Waffenarsenal", "parent" => null, "ordering" => 2],
            ["name" => "Box", "label" => "Taschen und Behälter", "parent" => null, "ordering" => 3],
            ["name" => "Armor", "label" => "Verteidigung", "parent" => null, "ordering" => 4],
            ["name" => "Drug", "label" => "Apotheke und Labor", "parent" => null, "ordering" => 5],
            ["name" => "Food", "label" => "Grundnahrungsmittel", "parent" => null, "ordering" => 6],
            ["name" => "Misc", "label" => "Sonstiges", "parent" => null, "ordering" => 7],
        ]);
    }
}