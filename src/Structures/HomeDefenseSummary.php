<?php


namespace App\Structures;

class HomeDefenseSummary
{
    public $house_defense = 0;
    public $job_defense = 0;
    public $job_guard_defense = 0;
    public $upgrades_defense = 0;
    public $item_defense = 0;

    public function sum(): int {
        return $this->house_defense + $this->job_defense + $this->job_guard_defense + $this->upgrades_defense + $this->item_defense;
    }
}