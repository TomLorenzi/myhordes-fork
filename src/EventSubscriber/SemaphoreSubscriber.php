<?php


namespace App\EventSubscriber;

use App\Annotations\Semaphore;
use App\Enum\SemaphoreScope;
use App\Service\Locksmith;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockInterface;
use Symfony\Bundle\SecurityBundle\Security;


class SemaphoreSubscriber implements EventSubscriberInterface
{
    private KernelInterface $kernel;
    private Security $security;
    private Locksmith $locksmith;

    /** @var LockInterface[]|null  */
    private array $locks = [];
    private array $lock_data = [];


    public function __construct(KernelInterface $kernel, Locksmith $locksmith, Security $security)
    {
        $this->kernel = $kernel;
        $this->locksmith = $locksmith;
        $this->security = $security;
    }

    public function lock(ControllerEvent $event) {

        /** @var Semaphore[] $semaphores */
        $semaphores = array_filter(
            $event->getRequest()->attributes->get('_Semaphores') ?? [],
            fn($e) => is_a( $e, Semaphore::class)
        );

        if (empty($semaphores)) return;

        usort( $semaphores, fn(Semaphore $a, Semaphore $b) =>
            $a->getScope()->order() <=> $b->getScope()->order() ?:
            strcmp( $a->getValue(), $b->getValue() )
        );

        foreach ($semaphores as $semaphore)
            if (!isset( $this->lock_data[$semaphore->getValue()] )) {
                $name = match ( $semaphore->getScope() ) {
                    SemaphoreScope::Global  => $semaphore->getValue(),
                    SemaphoreScope::User    => $semaphore->getValue() . ':{u' . ($this->security->getUser()?->getId() ?? 0) . '}',
                    SemaphoreScope::Town    => $semaphore->getValue() . ':{t' . ($this->security->getUser()?->getActiveCitizen()?->getTown()?->getId() ?? 0) . '}',
                    SemaphoreScope::None    => null,
                };

                if ($this->kernel->getEnvironment() === 'dev' || $this->kernel->getEnvironment() === 'local') $this->lock_data[$semaphore->getValue()] = $name;
                if ($name) $this->locks[] = $this->locksmith->waitForLock($name);
            }
    }

    public function unlock(ResponseEvent $event) {
        foreach ($this->locks as $lock) $lock->release();
        if ($this->kernel->getEnvironment() === 'dev' || $this->kernel->getEnvironment() !== 'local')
            foreach ($this->lock_data as $key)
                if ($key) $event->getResponse()->headers->set('X-Semaphores', $key, false);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['lock', -32],
            KernelEvents::RESPONSE   => ['unlock', -32],
        ];
    }
}