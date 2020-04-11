<?php


namespace App\Service;


use App\Entity\Town;
use App\Structures\TownConf;

class ConfMaster
{

    private $game_rules;

    public function __construct( array $rules) {
        $this->game_rules = $rules;
    }

    public function getTownConfiguration( Town $town ) {
        $tc = new TownConf( [$this->game_rules['default'], $this->game_rules[$town->getType()->getName()]] );
        if ($tc->get(TownConf::CONF_ALLOW_LOCAL, false) && $town->getConf()) $tc->import( $town->getConf() );
        return $tc->complete();
    }

}