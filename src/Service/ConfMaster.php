<?php


namespace App\Service;


use App\Entity\Town;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;

class ConfMaster
{
    private array $global;
    private array $game_rules;
    private array $events;

    private ?MyHordesConf $global_conf = null;
    private ?EventConf $event_conf = null;

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

    public function getCurrentEvent(): EventConf {
        if ($this->event_conf !== null)
            return $this->event_conf;

        $curDate = new \DateTime();

        foreach($this->events as $conf){

            if (empty($conf['trigger']) || empty($conf['trigger']['type'])) continue;

            switch ($conf['trigger']['type']) {
                case 'on':
                    return ($this->event_conf = (new EventConf( $conf['conf'] ))->complete());
                case 'datetime':
                    list($beginDate, $beginTime) = explode(' ', $conf['trigger']['begin']);
                    list($endDate, $endTime) = explode(' ', $conf['trigger']['end']);

                    $begin = (new \DateTime())->setDate((int)$curDate->format('Y'), explode('-', $beginDate)[0], explode('-', $beginDate)[1])->setTime(explode(':', $beginTime)[0], explode(':', $beginTime)[1], 0);
                    $end = (new \DateTime())->setDate((int)$curDate->format('Y'), explode('-', $endDate)[0], explode('-', $endDate)[1])->setTime(explode(':', $endTime)[0], explode(':', $endTime)[1], 0);

                    while ($begin > $end) $end->modify("+1 year");

                    if ($curDate >= $begin && $curDate <= $end)
                        return ($this->event_conf = (new EventConf( $conf['conf'] ))->complete());
                    break;
            }
        }

        return ($this->event_conf = (new EventConf( [] ))->complete());
    }
}
