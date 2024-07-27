<?php


namespace App\Structures;

class TownDefenseSummary
{
    public int $base_defense = 10;
    public int $house_defense = 0;
    public int $guardian_defense = 0;
    public int $citizen_defense = 0;
    public int $building_defense = 0;
    public int $building_def_base = 0;
    public int $building_def_vote = 0;
    public int $item_defense = 0;
    public float $nightwatch_defense = 0.0;
    public float $overall_scale = 1.0;
    public int $soul_defense = 0;
    public int $cemetery = 0;
    public int $temp_defense = 0;

    public function sum(): int {
        return round($this->overall_scale * ($this->base_defense + $this->item_defense + $this->building_def_base + $this->building_def_vote + $this->house_defense + $this->citizen_defense + $this->guardian_defense + $this->temp_defense + $this->nightwatch_defense + $this->soul_defense + $this->cemetery));
    }

    public function withoutItemDefense(): int {
        return round($this->overall_scale * ($this->base_defense + $this->building_def_base + $this->building_def_vote + $this->house_defense + $this->citizen_defense + $this->guardian_defense + $this->temp_defense + $this->nightwatch_defense + $this->soul_defense + $this->cemetery));
    }

    public function toArray(): array {
        return [
            'base' => $this->base_defense,
            'houses' => $this->house_defense,
            'citizen' => $this->citizen_defense,
            'guard' => $this->guardian_defense,
            'buildings_base' => $this->building_def_base,
            'buildings_vote' => $this->building_def_vote,
            'items' => $this->item_defense,
            'nightwatch' => $this->nightwatch_defense,
            'souls' => $this->soul_defense,
            'cemetery' => $this->cemetery,
            'temp' => $this->temp_defense,
            'scale' => $this->overall_scale
        ];
    }
}