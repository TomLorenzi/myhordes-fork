<?php

namespace App\Service;


use App\Entity\Town;
use App\Event\Game\SingleValue;
use App\Structures\EventConf;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Event\Game\EventHooks\Common\WatchtowerModifierEvent;

/**
 * @method void triggerPreAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerPostAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerNoAttackHooks(Town $town, EventConf|array $event)
 * @method JsonResponse|null triggerDoorResponseHooks(Town $town, EventConf|array $event, string $action)
 * @method WatchtowerModifierEvent|null triggerWatchtowerModifierHooks(Town $town, EventConf|array $event, int $min, int $max, int $dayOffset, float $quality)
 */
class GameEventService {
//int &$min, int &$max, Town $town, int $dayOffset, float $quality, ?string &$message = null
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
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function trigger( Town $town, EventConf|array $event, ?string $hook = null, ?string $dispatch = null, ...$args ): mixed {
        $value = null;
        if (is_array( $event )) foreach ($event as $e)
            $value ??= $this->trigger($town, $e, $hook, $dispatch, ...$args);
        else {
            $hook_function = $hook ? $event->get( $hook, null ) : null;
            if ($hook_function) $value = call_user_func( $hook_function, $town);

            $dispatch_event = $dispatch ? $event->get( $dispatch, null ) : null;
            if ($dispatch_event) {
                $value = $this->ef->gameEvent($dispatch_event, $town);
                $this->ed->dispatch(call_user_func([$value, 'setup'], ...$args));

                try {
                    $traits = array_keys((new \ReflectionClass($value::publicConfigurationClass()))->getTraits());
                } catch (\ReflectionException $e) {
                    $traits = [];
                }

                if (in_array( SingleValue::class, $traits )) $value = $value->value;
            }
        }

        return $value;
    }

    protected function getHooksFor( $name ) {
        return match ($name) {
            'preAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_PRE, EventConf::EVENT_DISPATCH_NIGHTLY_PRE ],
            'postAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_POST, EventConf::EVENT_DISPATCH_NIGHTLY_POST ],
            'noAttack' => [ EventConf::EVENT_HOOK_NIGHTLY_NONE, EventConf::EVENT_DISPATCH_NIGHTLY_NONE ],

            'doorResponse' => [ EventConf::EVENT_HOOK_DOOR, EventConf::EVENT_DISPATCH_DOOR ],

            'watchtowerModifier' => [ null, EventConf::EVENT_DISPATCH_WATCHTOWER ],
            default => [null,null]
        };
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with( $name, 'trigger' ) && str_ends_with( $name, 'Hooks' )) {
            [$hook,$dispatch] = $this->getHooksFor( lcfirst( substr( $name, 7, -5 ) ) );
            return call_user_func_array( [$this,'trigger'], [
                $arguments[0],
                $arguments[1],
                $hook,
                $dispatch,
                ...(array_slice( $arguments, 2 ))
            ] );
        } else throw new \Exception('Invalid magic call.');
    }

}