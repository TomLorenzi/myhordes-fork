<?php


namespace App\Service;


use App\Entity\Town;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;

class ConfMaster
{
    private $global;
    private $game_rules;

    private $global_conf;

    public function __construct( array $global, array $local, array $rules) {
        $this->global = array_merge($global,$local);
        $this->game_rules = $rules;
    }

    public function getGlobalConf(): MyHordesConf {
        return $this->global_conf ?? ( $this->global_conf = (new MyHordesConf($this->global))->complete() );
    }

    public function getTownConfiguration( Town $town ): TownConf {
        $tc = new TownConf( [$this->game_rules['default'], $this->game_rules[$town->getDeriveConfigFrom() ?? $town->getType()->getName()]] );
        if ($tc->complete()->get(TownConf::CONF_ALLOW_LOCAL, false) && $town->getConf()) $tc->import( $town->getConf() );
        return $tc->complete();
    }

}
