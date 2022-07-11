<?php


namespace App\Structures;

use App\Entity\CauseOfDeath;
use App\Entity\PictoPrototype;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Iterator;

class TwinoidPayload
{

    private array $_data;

    private EntityManagerInterface $em;

    /**
     * TwinoidPayload constructor.
     * @param array $data
     * @param EntityManagerInterface $em
     */
    public function __construct(array $data, EntityManagerInterface $em)
    {
        $this->_data = $data;
        $this->em = $em;
    }


    private array $_cache_cod = [];
    public function internal_fetch_cod(int $cod): ?CauseOfDeath {
        return $this->_cache_cod[$cod] ?? ($this->_cache_cod[$cod] = $this->em->getRepository(CauseOfDeath::class)->findOneByRef( $cod ));
    }

    private array $_cache_picto = [];
    public function internal_fetch_picto(string $p): ?PictoPrototype {
        return $this->_cache_picto[$p] ?? ($this->_cache_picto[$p] = $this->em->getRepository(PictoPrototype::class)->findOneBy( ['name' => $p] ));
    }

    public function getTwinoidName(): string {
        return $this->_data['name'];
    }

    public function getTwinoidId(): int {
        return $this->_data['twinId'];
    }

    public function getScopeId(): int {
        return $this->_data['id'];
    }

    private ?int $_cache_sp = null;
    public function getSummarySoulPoints(): int {
        if ($this->_cache_sp !== null) return $this->_cache_sp;
        $s1 = 0; $s2 = 0;
        foreach ($this->getPastTowns() as $town) $s1 += $town->getScore();
        foreach ($this->getPictos() as $picto) if ($picto->getID() === 'ptame') $s2 += $picto->getCount();

        return ($this->_cache_sp = max($s1,$s2));
    }

    private ?int $_cache_hd = null;
    public function getSummaryHeroDays(): int {
        if ($this->_cache_hd !== null) return $this->_cache_hd;
        $h = 0; $d = 0; $c = 0; $rd = 0;
        foreach ($this->getPictos() as $picto) {
            if (in_array($picto->getID(),['jtamer','jrangr','jermit','jcolle','jguard','jtech','jsham'])) $h += $picto->getCount();
            if ($picto->getID() === 'dcity') $rd++;
        }
        foreach ($this->getPastTowns() as $town) {
            $c += 1;
            $d += $town->getSurvivedDays();
        }

        $hd_rate = $d <= 0 ? 0 : max(0.0, min($h/$d, 1.0));
        $loss = $c - $rd;

        return ($this->_cache_hd = floor($h + ($loss * $hd_rate)));
    }

    public function getPastTowns(): Iterator
    {
        return new class($this->_data['playedMaps'] ?? $this->_data['cadavers'], $this) implements Iterator {
            private array $_towns;
            private int $_pos = 0;
            private TwinoidPayload $_parent;

            public function __construct(array $towns, TwinoidPayload $parent)
            {
                $this->_towns = $towns;
                $this->_parent = $parent;

                usort($this->_towns, function($a, $b) {
                    return
                        ($b['season'] <=> $a['season']) ?:
                            ($b['score'] <=> $a['score']) ?:
                                (($a['mapName'] ?? $a['name']) <=> ($b['mapName'] ?? $b['name']));
                });
            }

            public function current(): object
            {
                return new class($this->_towns[$this->_pos], $this->_parent) {

                    private array $_town;
                    private TwinoidPayload $_parent;

                    public function __construct(array $town, TwinoidPayload $parent)
                    {
                        $this->_town = $town;
                        $this->_parent = $parent;
                    }

                    public function getName():    string { return $this->_town['mapName'] ?? $this->_town['name']; }
                    public function getMessage(): string { return $this->_town['msg'] ?? ''; }
                    public function getComment(): string { return $this->_town['comment'] ?? $this->_town['m']; }

                    public function getSeason():       int { return $this->_town['season']; }
                    public function getScore():        int { return $this->_town['score']; }
                    public function getDay():          int { return $this->_town['day'] ?? $this->_town['d']; }
                    public function getSurvivedDays(): int { return $this->_town['survival'] ?? $this->_town['d']; }
                    public function getID():           int { return $this->_town['mapId'] ?? $this->_town['id']; }
                    public function getCleanup():    array { return $this->_town['cleanup'] ?? ['type' => null, 'user' => null]; }

                    public function getDeath():  int { return $this->_town['dtype'] ?? 0; }
                    public function isOld(): bool { return $this->_town['v1']; }

                    public function convertDeath(): CauseOfDeath {
                        return match ($this->getDeath()) {
                            1 => $this->_parent->internal_fetch_cod(CauseOfDeath::Dehydration),
                            2 => $this->_parent->internal_fetch_cod(CauseOfDeath::Strangulation),
                            3 => $this->_parent->internal_fetch_cod(CauseOfDeath::Cyanide),
                            4 => $this->_parent->internal_fetch_cod(CauseOfDeath::Hanging),
                            5 => $this->_parent->internal_fetch_cod(CauseOfDeath::Vanished),
                            6 => $this->_parent->internal_fetch_cod(CauseOfDeath::NightlyAttack),
                            7 => $this->_parent->internal_fetch_cod(CauseOfDeath::Addiction),
                            8 => $this->_parent->internal_fetch_cod(CauseOfDeath::Infection),
                            9, 10 => $this->_parent->internal_fetch_cod(CauseOfDeath::Headshot),
                            11 => $this->_parent->internal_fetch_cod(CauseOfDeath::Poison),
                            12 => $this->_parent->internal_fetch_cod(CauseOfDeath::GhulEaten),
                            13 => $this->_parent->internal_fetch_cod(CauseOfDeath::GhulBeaten),
                            14 => $this->_parent->internal_fetch_cod(CauseOfDeath::GhulStarved),
                            15 => $this->_parent->internal_fetch_cod(CauseOfDeath::FleshCage),
                            16 => $this->_parent->internal_fetch_cod(CauseOfDeath::ChocolateCross),
                            17 => $this->_parent->internal_fetch_cod(CauseOfDeath::ExplosiveDoormat),
                            default => $this->_parent->internal_fetch_cod(CauseOfDeath::Unknown),
                        };
                    }

                };
            }

            public function next(): void { $this->_pos++; }
            public function key(): int { return $this->_pos; }
            public function valid(): bool { return $this->_pos < count($this->_towns); }
            public function rewind(): void { $this->_pos = 0; }
        };
    }

    public function getPictos(): Iterator
    {

        return new class($this->_data['stats'], $this) implements Iterator {
            private array $_stats;
            private int $_pos = 0;
            private TwinoidPayload $_parent;

            public function __construct(array $stats, TwinoidPayload $parent)
            {
                $this->_stats = $stats;
                $this->_parent = $parent;

                usort($this->_stats, function($a, $b) {
                    return
                        ($b['rare'] <=> $a['rare']) ?:
                            ($b['score'] <=> $a['score']) ?:
                                ($b['name'] <=> $a['name']);
                });
            }

            public function current(): object
            {
                return new class($this->_stats[$this->_pos], $this->_parent) {

                    private array $_stat;
                    private TwinoidPayload $_parent;

                    public function __construct(array $stat, TwinoidPayload $parent)
                    {
                        $this->_stat = $stat;
                        $this->_parent = $parent;
                    }

                    public function getName():   string { return $this->_stat['name']; }
                    public function getID():     string { return $this->_stat['id']; }

                    public function getRarity(): int { return $this->_stat['rare']; }
                    public function getCount():  int { return $this->_stat['score']; }

                    public function getSocial(): bool { return $this->_stat['social']; }

                    public function convertPicto(): ?PictoPrototype {
                        return $this->_parent->internal_fetch_picto( "r_{$this->getID()}_#00" );
                    }

                };
            }

            public function next(): void { $this->_pos++; }
            public function key(): int { return $this->_pos; }
            public function valid(): bool { return $this->_pos < count($this->_stats); }
            public function rewind(): void { $this->_pos = 0; }
        };
    }

    public function getUnlockables(): Iterator
    {

        return new class($this->_data['achievements'], $this) implements Iterator {
            private array $_achs;
            private int $_pos = 0;
            private TwinoidPayload $_parent;

            public function __construct(array $achs, TwinoidPayload $parent)
            {
                $this->_achs = $achs;
                $this->_parent = $parent;

                usort($this->_achs, function($a, $b) {
                    return
                        ($b['data']['type'] <=> $a['data']['type']) ?:
                            ($a['id'] <=> $b['id']);
                });
            }

            public function current(): object
            {
                return new class($this->_achs[$this->_pos], $this->_parent) {

                    private array $_ach;
                    private TwinoidPayload $_parent;

                    public function __construct(array $stat, TwinoidPayload $parent)
                    {
                        $this->_ach = $stat;
                        $this->_parent = $parent;
                    }

                    public function getName(): string { return $this->_ach['name']; }
                    public function getType(): string { return $this->_ach['data']['type']; }
                    public function getNiceType(): string {
                        return match ($this->getType()) {
                            'title' => T::__('Titel', 'soul'),
                            'icon' => T::__('Icon', 'soul'),
                            default => $this->getType(),
                        };
                    }
                    public function getData(): string {
                        return match ($this->getType()) {
                            'title' => $this->_ach['data']['title'],
                            'icon' => $this->_ach['data']['url'],
                            default => '',
                        };
                    }
                    public function getDate(): DateTime { return new DateTime($this->_ach['date']); }

                    public function getPicto(): string { return $this->_ach['stat']; }
                    public function getCount(): int { return $this->_ach['score']; }

                    public function convertPicto(): ?PictoPrototype {
                        return $this->_parent->internal_fetch_picto( "r_{$this->getPicto()}_#00" );
                    }

                };
            }

            public function next(): void { $this->_pos++; }
            public function key(): int { return $this->_pos; }
            public function valid(): bool { return $this->_pos < count($this->_achs); }
            public function rewind(): void { $this->_pos = 0; }
        };
    }
}