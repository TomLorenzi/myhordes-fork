<?php


namespace App\Structures;


use App\Entity\Citizen;
use App\Entity\HeroicActionPrototype;

class FriendshipActionTarget
{
    public function __construct(
        private HeroicActionPrototype $action,
        private Citizen $citizen
    ) {}

    public function citizen(): Citizen {
        return $this->citizen;
    }

    public function action(): HeroicActionPrototype {
        return $this->action;
    }
}