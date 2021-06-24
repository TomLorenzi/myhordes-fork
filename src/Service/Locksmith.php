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

    public function getLock( string $name ): LockInterface {
        return $this->lock_factory->createLock( $name );
    }

    public function getAcquiredLock( string $name ): ?LockInterface {
        $lock = $this->getLock( $name );
        if ($lock->acquire()) return $lock;
        else return null;
    }

    public function waitForLock( string $name ): LockInterface {
        $lock = $this->getLock( $name );
        $lock->acquire( true );
        return $lock;
    }

}
