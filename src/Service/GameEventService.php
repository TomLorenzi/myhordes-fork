<?php

namespace App\Service;


use App\Entity\Town;
use App\Structures\EventConf;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method void triggerPreAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerPostAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerNoAttackHooks(Town $town, EventConf|array $event)
 */
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
     * @param mixed ...$args
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function trigger( Town $town, EventConf|array $event, ?string $hook = null, ?string $dispatch = null, ...$args ): void {
        if (is_array( $event )) foreach ($event as $e) $this->trigger( $town, $e, $hook, $dispatch, ...$args );
        else {
            $hook_function = $hook ? $event->get( $hook, null ) : null;
            if ($hook_function) call_user_func( $hook_function, $town);

            $dispatch_event = $dispatch ? $event->get( $dispatch, null ) : null;
            if ($dispatch_event) $this->ed->dispatch( call_user_func( [$this->ef->gameEvent( $dispatch_event, $town ), 'setup'], ...$args ) );
        }
    }

    protected function getHooksFor( $name ) {
        return match ($name) {
            'preAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_PRE, EventConf::EVENT_DISPATCH_NIGHTLY_PRE ],
            'postAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_POST, EventConf::EVENT_DISPATCH_NIGHTLY_POST ],
            'noAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_NONE, EventConf::EVENT_DISPATCH_NIGHTLY_NONE ],
            default => [null,null]
        };
    }

    public function __call(string $name, array $arguments)
    {
        if (str_starts_with( $name, 'trigger' ) && str_ends_with( $name, 'Hooks' )) {
            [$hook,$dispatch] = $this->getHooksFor( lcfirst( substr( $name, 7, -5 ) ) );
            call_user_func_array( [$this,'trigger'], [
                $arguments[0],
                $arguments[1],
                $hook,
                $dispatch,
                ...(array_slice( $arguments, 2 ))
            ] );
        } else throw new \Exception('Invalid magic call.');
    }

}