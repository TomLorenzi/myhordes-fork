<?php


namespace App\Structures;

class TownDefenseSummary
{
    public $base_defense = 10;
    public $house_defense = 0;
    public $guardian_defense = 0;
    public $building_defense = 0;
    public $building_def_base = 0;
    public $building_def_vote = 0;
    public $item_defense = 0;
    public $nightwatch_defense = 0.0;
    public $overall_scale = 1.0;
    public $soul_defense = 0;
    public $cemetery = 0;
    public $temp_defense = 0;

    public function sum(): int {
        return $this->overall_scale * ($this->base_defense + $this->house_defense + $this->guardian_defense + $this->building_def_base + $this->building_def_vote + $this->item_defense + $this->nightwatch_defense + $this->soul_defense + $this->temp_defense + $this->cemetery);
    }
}