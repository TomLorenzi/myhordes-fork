<?php

namespace App\Service;


use App\Entity\Citizen;
use App\Entity\Town;
use App\Event\Game\SingleValue;
use App\Structures\EventConf;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Event\Game\EventHooks\Common\WatchtowerModifierEvent;
use App\Event\Game\EventHooks\Common\DashboardModifierEvent;

/**
 * @method bool triggerEnableTownHooks(Town $town, EventConf|array $event)
 * @method bool triggerDisableTownHooks(Town $town, EventConf|array $event)
 * @method bool triggerEnableCitizenHooks(Citizen $citizen, EventConf|array $event)
 * @method bool triggerDisableCitizenHooks(Citizen $citizen, EventConf|array $event)
 * @method void triggerPreAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerPostAttackHooks(Town $town, EventConf|array $event)
 * @method void triggerNoAttackHooks(Town $town, EventConf|array $event)
 * @method JsonResponse|null triggerDoorResponseHooks(Town $town, EventConf|array $event, string $action)
 * @method WatchtowerModifierEvent|null triggerWatchtowerModifierHooks(Town $town, EventConf|array $event, int $min, int $max, int $dayOffset, float $quality)
 * @method DashboardModifierEvent|null triggerDashboardModifierHooks(Town $town, EventConf|array $event)
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
     * @param string $dispatch
     * @param mixed ...$args
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function trigger( Town $town, EventConf|array $event, string $dispatch, ...$args ): mixed {
        $value = null;
        if (is_array( $event )) foreach ($event as $e)
            $value ??= $this->trigger($town, $e, $dispatch, ...$args);
        elseif ($dispatch_event = $event->get( $dispatch, null )) {
            $value = $this->ef->gameEvent($dispatch_event, $town);
            $this->ed->dispatch(call_user_func([$value, 'setup'], ...$args));

            try {
                $traits = array_keys((new \ReflectionClass($value::publicConfigurationClass()))->getTraits());
            } catch (\ReflectionException $e) {
                $traits = [];
            }

            if (in_array( SingleValue::class, $traits )) $value = $value->value;
        }

        return $value;
    }

    protected function getHooksFor( $name ): ?string {
        return match ($name) {
            'enableTown' => EventConf::EVENT_DISPATCH_ENABLE_TOWN,
            'disableTown' => EventConf::EVENT_DISPATCH_DISABLE_TOWN,

            'enableCitizen'  => EventConf::EVENT_DISPATCH_ENABLE_CITIZEN,
            'disableCitizen' => EventConf::EVENT_DISPATCH_DISABLE_CITIZEN,

            'preAttack' => EventConf::EVENT_DISPATCH_NIGHTLY_PRE,
            'postAttack' => EventConf::EVENT_DISPATCH_NIGHTLY_POST,
            'noAttack' => EventConf::EVENT_DISPATCH_NIGHTLY_NONE,

            'doorResponse' => EventConf::EVENT_DISPATCH_DOOR,

            'watchtowerModifier' => EventConf::EVENT_DISPATCH_WATCHTOWER,
            'dashboardModifier' => EventConf::EVENT_DISPATCH_DASHBOARD,

            default => null
        };
    }

    protected function getDefaultResponseFor( $name ): mixed {
        return match ($name) {
            'enableTown',
            'disableTown',
            'enableCitizen',
            'disableCitizen' => true,
            default => null
        };
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with( $name, 'trigger' ) && str_ends_with( $name, 'Hooks' )) {
            $hookName = lcfirst( substr( $name, 7, -5 ) );
            if (!$dispatch = $this->getHooksFor( $hookName )) return $this->getDefaultResponseFor( $hookName );

            $town = is_a( $arguments[0], Citizen::class ) ? $arguments[0]->getTown() : $arguments[0];
            $inferred = is_a( $arguments[0], Citizen::class ) ? [$arguments[0]] : [];

            return call_user_func_array( [$this,'trigger'], [
                $town,
                $arguments[1],
                $dispatch,
                ...$inferred,
                ...(array_slice( $arguments, 2 ))
            ] ) ?? $this->getDefaultResponseFor( $hookName );
        } else throw new \Exception('Invalid magic call.');
    }

}