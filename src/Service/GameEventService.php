<?php

namespace App\Service;


use App\Entity\Town;
use App\Structures\EventConf;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GameEventService {

    public function __construct(
        protected readonly ConfMaster $conf,
        protected readonly EventDispatcherInterface $ed,
        protected readonly EventFactory $ef
    ) { }

    /**
     * @param Town $town
     * @param EventConf|array<EventConf> $event
     * @param string|null $hook
     * @param string|null $dispatch
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function trigger( Town $town, EventConf|array $event, ?string $hook = null, ?string $dispatch = null ): void {
        if (is_array( $event )) foreach ($event as $e) $this->trigger( $e, $hook, $dispatch );
        else {
            $hook_function = $hook ? $event->get( $hook, null ) : null;
            if ($hook_function) call_user_func( $hook_function, $town);

            $dispatch_event = $dispatch ? $event->get( $dispatch, null ) : null;
            if ($dispatch_event) $this->ed->dispatch( $this->ef->gameEvent( $dispatch_event, $town )->setup( ) );
        }
    }

}