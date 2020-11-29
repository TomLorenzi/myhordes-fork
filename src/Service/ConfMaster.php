<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\EventActivationMarker;
use App\Entity\Town;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

class ConfMaster
{
    private EntityManagerInterface $entityManager;

    private array $global;
    private array $game_rules;
    private array $events;

    private ?MyHordesConf $global_conf = null;
    private ?EventConf $event_conf = null;
    private array $event_cache = [];

    public function __construct( array $global, array $local, array $rules, array $events, EntityManagerInterface $em) {
        $this->global = array_merge($global,$local);
        $this->game_rules = $rules;
        $this->events = $events;
        $this->entityManager = $em;
    }

    public function getGlobalConf(): MyHordesConf {
        return $this->global_conf ?? ( $this->global_conf = (new MyHordesConf($this->global))->complete() );
    }

    public function getTownConfiguration( Town $town ): TownConf {
        $tc = new TownConf( [$this->game_rules['default'], $this->game_rules[$town->getDeriveConfigFrom() ?? $town->getType()->getName()]] );
        if ($tc->complete()->get(TownConf::CONF_ALLOW_LOCAL, false) && $town->getConf()) $tc->import( $town->getConf() );
        return $tc->complete();
    }

    public function getEvent(string $name): EventConf {
        return $this->event_cache[$name] ?? ($this->event_cache[$name] = isset($this->events[$name])
            ? (new EventConf( $name, $this->events[$name]['conf'] ))->complete()
            : (new EventConf())->complete());
    }

    /**
     * @param Town|Citizen|null $ref
     * @param EventActivationMarker|null $marker
     * @return EventConf
     */
    public function getCurrentEvent( $ref = null, ?EventActivationMarker &$marker = null ): EventConf {
        $marker = null;
        if ($ref !== null) {

            if (is_a($ref, Town::class))
                $marker = $this->entityManager->getRepository(EventActivationMarker::class)->findOneBy(['town' => $ref, 'active' => true]);
            elseif (is_a($ref, Citizen::class))
                $marker = $this->entityManager->getRepository(EventActivationMarker::class)->findOneBy(['citizen' => $ref, 'active' => true]);
            else throw new \LogicException('Queried current event from an object that is not referenced by EventActivationMarker.');

            return $marker ? $this->getEvent( $marker->getEvent() ) : new EventConf();
        }

        if ($this->event_conf !== null)
            return $this->event_conf;

        $curDate = new \DateTime();

        foreach($this->events as $id => $conf){

            if (empty($conf['trigger']) || empty($conf['trigger']['type'])) continue;

            switch ($conf['trigger']['type']) {
                case 'on':
                    return $this->event_conf = $this->getEvent($id);
                case 'datetime':
                    list($beginDate, $beginTime) = explode(' ', $conf['trigger']['begin']);
                    list($endDate, $endTime) = explode(' ', $conf['trigger']['end']);

                    $begin = (new \DateTime())->setDate((int)$curDate->format('Y'), explode('-', $beginDate)[0], explode('-', $beginDate)[1])->setTime(explode(':', $beginTime)[0], explode(':', $beginTime)[1], 0);
                    $end = (new \DateTime())->setDate((int)$curDate->format('Y'), explode('-', $endDate)[0], explode('-', $endDate)[1])->setTime(explode(':', $endTime)[0], explode(':', $endTime)[1], 0);

                    while ($begin > $end) $end->modify("+1 year");

                    if ($curDate >= $begin && $curDate < $end)
                        return $this->event_conf = $this->getEvent($id);
                    break;
            }
        }

        return ($this->event_conf = (new EventConf())->complete());
    }
}
