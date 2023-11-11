<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\EventActivationMarker;
use App\Entity\ServerSettings;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Enum\Configuration\TownSetting;
use App\Enum\ServerSetting;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Plugins\Management\ConfigurationStorage;

class ConfMaster
{
    private ?MyHordesConf $global_conf = null;
    private ?array $event_conf = null;
    private array $event_cache = [];
    private array $setting_cache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfigurationStorage $storage,
    ) {}

    public function getGlobalConf(): MyHordesConf {
        return $this->global_conf ?? ( $this->global_conf = (new MyHordesConf($this->storage->getSegment('myhordes')))->complete() );
    }

    public function getTownConfiguration( Town $town ): TownConf {
        $tc = new TownConf( [
            $this->storage->getSegment('rules.default'),
            $town->getDeriveConfigFrom() ? $this->storage->getSegment("rules.{$town->getDeriveConfigFrom()}", []) : [],
            $this->storage->getSegment("rules.{$town->getType()->getName()}", [])
        ] );
        if ($tc->complete()->get(TownSetting::AllowLocalConfiguration) && $town->getConf()) $tc->import( $town->getConf() );
        return $tc->complete();
    }

    public function getTownConfigurationByType( TownClass|string|null $town = null, bool $asPrivate = false ): TownConf {
        if (is_a( $town, TownClass::class )) $town = $town->getName();
        else $town = $town ?? 'default';
        return (new TownConf( [
            $this->storage->getSegment('rules.default'),
            $this->storage->getSegment("rules.$town", []),
            $asPrivate ? $this->storage->getSegment('rules.custom', []) : []
        ] ))->complete();
    }

    public function getEvent(string $name): EventConf {
        return $this->event_cache[$name] ?? ($this->event_cache[$name] = $this->storage->hasSegment("events.$name")
            ? (new EventConf( $name, $this->storage->getSegment("events.$name.conf", []) ))->complete()
            : (new EventConf())->complete());
    }

    public function getEventScheduleByName(string $name, DateTime $curDate, ?DateTime &$begin = null, ?DateTime &$end = null, bool $lookAhead = false): bool {
        if (!$this->storage->hasSegment("events.$name") || !$this->storage->hasSegment("events.$name.trigger")) return false;
        return $this->getEventSchedule( $this->storage->getSegment("events.$name.trigger", []), $curDate, $begin, $end, $lookAhead );
    }

    public function eventIsPublic(string $name): bool {
        return $this->storage->getSegment( "events.$name.public", false );
    }

    public function getAllEvents(): array {
        return $this->storage->getSegment( 'events', [] );
    }

    public function getAllEventNames(): array {
        return array_keys($this->getAllEvents());
    }

    public function getEventSchedule(array $trigger, DateTime $curDate, ?DateTime &$begin = null, ?DateTime &$end = null, bool $lookAhead = false): bool {
        $begin = $end = null;
        if (empty($trigger['type'])) return false;

        switch ($trigger['type']) {
            case 'on':
                return true;
            case 'datetime':
                list($beginDate, $beginTime) = explode(' ', $trigger['begin']);
                list($endDate, $endTime) = explode(' ', $trigger['end']);

                $beginDate = explode('-', $beginDate);
                $endDate = explode('-', $endDate);

                $beginTime = explode(':', $beginTime);
                $endTime = explode(':', $endTime);

                if (count($beginDate) !== count($endDate) || count($beginDate) < 2 || count($beginDate) > 3) return false;
                if (count($beginDate) === 2) {
                    array_unshift($beginDate,(int)$curDate->format('Y'));
                    array_unshift($endDate,(int)$curDate->format('Y'));
                }

                $begin = (new DateTime())->setDate($beginDate[0], $beginDate[1], $beginDate[2])->setTime($beginTime[0], $beginTime[1], 0);
                $end = (new DateTime())->setDate($endDate[0], $endDate[1], $endDate[2])->setTime($endTime[0], $endTime[1], 0);

                if ($begin < $curDate && $end < $curDate && $lookAhead) return $this->getEventSchedule( $trigger, (new DateTime())->setDate((int)$curDate->format('Y') + 1, 1, 1), $begin, $end, false );

                break;
            case 'easter':
                $y = (int)$curDate->format('Y');

                $k = floor( $y / 100 );
                $q = floor( $k /   4 );
                $d = (19 * ($y % 19) + ((15 + $k - ( floor( (8*$k + 13) / 25 ) ) - $q) % 30)) % 30;

                $d_offset = $d + ((2 * ($y % 4) + 4 * ($y % 7) + 6 * $d + ((4  + $k - $q) % 7)) % 7);

                $origin = DateTimeImmutable::createFromMutable((new DateTime( "$y-3-22"))->add( new DateInterval("P{$d_offset}D") ));

                $begin = DateTime::createFromImmutable($origin->sub( new DateInterval("P{$trigger['before']}D") ));
                $end = DateTime::createFromImmutable($origin->add( new DateInterval("P{$trigger['after']}D") ));

                if ($begin < $curDate && $end < $curDate && $lookAhead) return $this->getEventSchedule( $trigger, (new DateTime())->setDate((int)$curDate->format('Y') + 1, 1, 1), $begin, $end, false );

                break;
        }

        if ($begin > $end) {
            if ($curDate < $end) $begin->modify("-1 year");
            else $end->modify("+1 year");
        }

        if ($curDate >= $begin && $curDate < $end)
            return true;

        return false;
    }

    /**
     * @param Town|Citizen|null $ref
     * @param array|null $markers
     * @param DateTime|null $query_date
     * @return EventConf[]
     */
    public function getCurrentEvents( $ref = null, ?array &$markers = [], ?DateTime $query_date = null): array {
        $markers = [];
        if ($ref !== null) {

            if (is_a($ref, Town::class))
                $markers = $this->entityManager->getRepository(EventActivationMarker::class)->findBy(['town' => $ref, 'active' => true]);
            elseif (is_a($ref, Citizen::class))
                $markers = $this->entityManager->getRepository(EventActivationMarker::class)->findBy(['citizen' => $ref, 'active' => true]);
            else throw new \LogicException('Queried current event from an object that is not referenced by EventActivationMarker.');

            if (empty($markers)) return [(new EventConf())->complete()];

            $conf = array_map( fn(EventActivationMarker $m) => $this->getEvent( $m->getEvent() ), $markers );
            usort($conf, fn(EventConf $a, EventConf $b) => $b->priority() <=> $a->priority());
            return $conf;
        }

        if ($this->event_conf !== null) return $this->event_conf;

        $curDate = $query_date ?? new DateTime();

        $conf = [];
        foreach($this->storage->getSegment( 'events', [] ) as $id => $config) {

            if (empty($config['trigger'])) continue;

            if ($this->getEventSchedule($config['trigger'], $curDate))
                $conf[] = $this->getEvent($id);

        }

        return $this->event_conf = (empty($conf) ? [(new EventConf())->complete()] : $conf);
    }

    /**
     * @param Town $town
     * @param array $must_enable
     * @param array $must_disable
     * @param array $must_keep
     * @return bool Returns false if the current event configuration of the town does not match the scheduled event config
     */
    public function checkEventActivation( Town $town, array &$must_enable = [], array &$must_disable = [], array &$must_keep = [] ): bool {
        $current_events = $this->getCurrentEvents();

        // Check which events need to be configured
        $town_events = $this->getCurrentEvents($town);
        $ce = [];
        foreach ($current_events as $event) if ($event->active()) $ce[$event->name()] = 1;
        foreach ($town_events as $event) if ($event->active())
            if (isset($ce[$event->name()])) $ce[$event->name()] = 0;
            else $ce[$event->name()] = -1;

        $must_enable  = array_keys(array_filter($ce, fn(int $v) => $v > 0));
        $must_keep    = array_keys(array_filter($ce, fn(int $v) => $v === 0));
        $must_disable = array_keys(array_filter($ce, fn(int $v) => $v < 0));

        return empty($must_enable) && empty($must_disable);
    }

    public function getAllScheduledEvents(DateTime $from, DateTime $to, string $interval = '1D'): array {

        $cache = [[],[]];

        while ($from < $to) {

            foreach($this->storage->getSegment( 'events', [] ) as $id => $conf) {
                if (empty($conf['trigger'])) continue;

                $this->getEventSchedule($conf['trigger'], $from, $begin, $end);

                if ($begin !== null && $end !== null) {

                    if (!isset($cache[0][$id])) {
                        $cache[0][$id] = [$begin];
                        $cache[1][$id] = [$end];
                    } elseif (($begin <> end( $cache[0][$id] ) || $end <> end( $cache[1][$id] )) && $begin < $to) {
                        $cache[0][$id][] = $begin;
                        $cache[1][$id][] = $end;
                    }

                }

                $from->add(new DateInterval("P{$interval}"));
            }

        }

        $result = [];
        foreach ($cache[0] as $id => $dates) foreach ($dates as $date) $result[] = [ $id, $date, true ];
        foreach ($cache[1] as $id => $dates) foreach ($dates as $date) $result[] = [ $id, $date, false ];

        usort($result, fn(array $a, array $b) => $a[1] <=> $b[1] ?: $a[2] <=> $b[2] ?: strcmp($a[0],$b[0]) );
        return $result;
    }

    protected function fetchServerSetting(ServerSetting $setting): mixed {
        $data = $this->entityManager->getRepository(ServerSettings::class)->findOneBy(['setting' => $setting->value])?->getData();
        return $data ? $setting->decodeValue( $data ) : $setting->default();
    }

    public function serverSetting(ServerSetting $setting): mixed {
        return $this->setting_cache[$setting->value] ?? ($this->setting_cache[$setting->value] = $this->fetchServerSetting($setting));
    }
}
