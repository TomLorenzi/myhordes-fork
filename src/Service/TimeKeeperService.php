<?php


namespace App\Service;


use App\Entity\AttackSchedule;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class TimeKeeperService
{
    private EntityManagerInterface $entity_manager;
    private string $env;

    public function __construct(EntityManagerInterface $em, string $env)
    {
        $this->entity_manager = $em;
        $this->env = $env;
    }

    /**
     * Returns the DateTime for the last attack
     * @param DateTimeInterface|null $time
     * @return DateTimeInterface
     */
    public function getLastAttackTime( ?DateTimeInterface $time = null ): DateTimeInterface {
        /** @var AttackSchedule|null $s */
        $s = $this->entity_manager->getRepository( AttackSchedule::class )->findPrevious( $time );
        return $s ? $s->getTimestamp() : (new DateTime('2020-01-01 00:00:00'));
    }

    /**
     * Returns the DateTime for the next attack
     * @param DateTimeInterface|null $time
     * @return DateTimeInterface
     */
    public function getNextAttackTime( ?DateTimeInterface $time = null ): DateTimeInterface {
        /** @var AttackSchedule|null $s */
        $s = $this->entity_manager->getRepository( AttackSchedule::class )->findNext( $time );
        if ($s) return $s->getTimestamp();
        else {
            if ($time === null) $time = new DateTime('now');
            return ((clone $time))->modify( 'tomorrow' );
        }
    }

    /**
     * Returns the DateTime for the next attack that has not been completed yet. This will return the DateTime of the
     * next attack (i.e. a DateTime in the future, just like getNextAttackTime()), except during an attack, where it
     * will return the starting point of the currently running attack (i.e. a DateTime in the past).
     * @return DateTimeInterface
     */
    public function getCurrentAttackTime( ): DateTimeInterface {
        /** @var AttackSchedule|null $s */
        $s = $this->entity_manager->getRepository( AttackSchedule::class )->findNextUncompleted( );
        return $s ? $s->getTimestamp() : ((new DateTime('now'))->modify( 'tomorrow' ));
        //return $this->isDuringAttack( $time ) ? $this->getLastAttackTime( $time ) : $this->getNextAttackTime( $time );
    }

    public function sinceLastAttack( ?DateTimeInterface $time = null ): DateInterval {
        return ($time ?? new DateTime('now'))->diff( $this->getLastAttackTime( $time ) );
    }

    public function untilNextAttack( ?DateTimeInterface $time = null ): DateInterval {
        return ($time ?? new DateTime('now'))->diff( $this->getNextAttackTime( $time ) );
    }

    public function secondsUntilNextAttack( ?DateTimeInterface $time = null, bool $sticky = false ): int {
        if ($sticky && $this->isDuringAttack($time)) return 0;
        $dif = $this->untilNextAttack($time);
        return $dif->s + ($dif->i*60) + ($dif->h*3600) + ($dif->d*86400);
    }

    public function minutesUntilNextAttack( ?DateTimeInterface $time = null, bool $sticky = false ): int {
        if ($sticky && $this->isDuringAttack($time)) return 0;
        $dif = $this->untilNextAttack($time);
        return $dif->i + ($dif->h*60) + ($dif->d*1440);
    }

    public function hoursUntilNextAttack( ?DateTimeInterface $time = null, bool $sticky = false ): int {
        if ($sticky && $this->isDuringAttack($time)) return 0;
        $dif = $this->untilNextAttack($time);
        return $dif->h + ($dif->d*24);
    }

    public function isDuringAttack( ?DateTimeInterface $time = null ): bool {
        if ($this->getCurrentAttackTime() < ($time ?? new DateTime('now'))) return true;
        if ($this->env === 'dev' || $this->env === 'local') return false;
        else {
            $dif = $this->sinceLastAttack( $time );
            return ( $dif->i < 20 && $dif->h === 0 && $dif->d === 0 );
        }
    }

}