<?php


namespace App\Service;


use App\Entity\Town;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;

class ConfMaster
{
    private $global;
    private $game_rules;
    private $events;

    private $global_conf;

    public function __construct( array $global, array $local, array $rules, array $events) {
        $this->global = array_merge($global,$local);
        $this->game_rules = $rules;
        $this->events = $events;
    }

    public function getGlobalConf(): MyHordesConf {
        return $this->global_conf ?? ( $this->global_conf = (new MyHordesConf($this->global))->complete() );
    }

    public function getTownConfiguration( Town $town ): TownConf {
        $tc = new TownConf( [$this->game_rules['default'], $this->game_rules[$town->getDeriveConfigFrom() ?? $town->getType()->getName()]] );
        if ($tc->complete()->get(TownConf::CONF_ALLOW_LOCAL, false) && $town->getConf()) $tc->import( $town->getConf() );
        return $tc->complete();
    }

    public function getCurrentEvent(): ?array {
        $curDate = new \DateTime();
        $begin = new \DateTime();
        $end = new \DateTime();
        foreach($this->events as $conf){
            $beginDate = explode(' ', $conf['begin'])[0];
            $beginTime = explode(' ', $conf['begin'])[1];
            $endDate = explode(' ', $conf['end'])[0];
            $endTime = explode(' ', $conf['end'])[1];

            $begin = $begin->setDate($begin->format('Y'), explode('-', $beginDate)[0], explode('-', $beginDate)[1])->setTime(explode(':', $beginTime)[0], explode(':', $beginTime)[1], 0);
            $end = $end->setDate($end->format('Y'), explode('-', $endDate)[0], explode('-', $endDate)[1])->setTime(explode(':', $endTime)[0], explode(':', $endTime)[1], 0);

            if($curDate >= $begin && $curDate <= $end)
                return $conf;
        }

        return null;
    }
}
