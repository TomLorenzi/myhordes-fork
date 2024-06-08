<?php


namespace App\Service;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

class Locksmith {

    private ?LockFactory $lock_factory = null;

    public function __construct() {
        $this->lock_factory = new LockFactory(
            extension_loaded('sysvmsg') ? new SemaphoreStore() : new FlockStore() );
    }

    public function getLock( string $name, ?float $ttl = null ): LockInterface {
        return $this->lock_factory->createLock( $name, $ttl );
    }

    public function getAcquiredLock( string $name, ?float $ttl = null ): ?LockInterface {
        $lock = $this->getLock( $name, $ttl );
        if ($lock->acquire()) return $lock;
        else return null;
    }

    public function waitForLock( string $name, ?float $ttl = null ): LockInterface {
        $lock = $this->getLock( $name, $ttl );
        $lock->acquire( true );
        return $lock;
    }

}
