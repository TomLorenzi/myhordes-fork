<?php


namespace App\EventSubscriber;

use App\Annotations\AdminLogProfile;
use App\Service\AdminLog;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminLogGeneratorSubscriber implements EventSubscriberInterface
{
    private ContainerInterface $container;
    private ?ControllerArgumentsEvent $event = null;
    private ?AdminLogProfile $conf = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function prepareLogging(ControllerArgumentsEvent $event) {
        $this->conf = $event->getRequest()->attributes->get('_AdminLogProfile') ?? null;
        $this->event = $this->conf?->enableLogging() ? $event: null;
    }

    private function flatten_array( array $data, array &$flat, ?string $prefix = null ): void {
        array_walk( $data, function ($value, $key) use ($prefix, &$flat) {
            $current_key = $prefix === null ? $key : "$prefix.$key";
            if (is_array( $value )) $this->flatten_array( $value, $flat, $current_key );
            else {
                if (is_string( $value ) && mb_strlen( $value ) > 64) $value = mb_substr($value, 0, 63) . '…';
                elseif (is_string( $value ) && mb_strlen( $value ) === 0) $value = "''";
                $flat[$current_key] = $value;
            }
        } );
    }

    public function ensureLogging() {
        if ($this->event) {
            /** @var AdminLog $logger */
            $logger = $this->container->get( AdminLog::class );

            if ($logger->has_been_invoked()) return;

            $controller = $this->event->getController();
            if (is_array($controller)) $controller = $controller[0];

            $controller_name = get_class( $controller );
            $route_name = $this->event->getRequest()->attributes->get('_route', '???');

            $flat_json_params = $json_params = [];
            if ($this->event->getRequest()->getContentTypeFormat() === 'json') {
                $json_params = json_decode($this->event->getRequest()->getContent(), true, 512, JSON_INVALID_UTF8_IGNORE ) ?? [];
                $this->flatten_array( $json_params, $flat_json_params );
                $flat_json_params = array_map( fn($k,$v) => $this->conf->isMasked("$.$k") ? "[$k = <fg=#a8a8c0>#MASKED</fg=#a8a8c0>]" : "[$k = <fg=#a8a8a8>$v</fg=#a8a8a8>]", array_keys( $flat_json_params ), $flat_json_params );
            }

            $query_params = $this->event->getRequest()->attributes->get('_route_params', []);
            $query_params = array_filter( $query_params, fn($v,$k) => !is_array($v) && !isset($json_params[$k]), ARRAY_FILTER_USE_BOTH );
            $query_params = array_map( fn($k,$v) => $this->conf->isMasked($k) ? "[$k = <fg=#a8a8c0>#MASKED</fg=#a8a8c0>]" : "[$k = <fg=#a8a8a8>$v</fg=#a8a8a8>]", array_keys( $query_params ), $query_params );

            $logger->invoke(
                "Invoked <comment>{$route_name}</comment> within <info>{$controller_name}</info>" .
                ( !empty($query_params) ? (' with <fg=#707070>' . implode(' ', $query_params) . '</fg=#707070>' ) : '' ) .
                ( !empty($flat_json_params) ? (' using <fg=#707070>' . implode(' ', $flat_json_params) . '</fg=#707070>' ) : '' ));
        }
    }

    /**
     * @inheritDoc
     */
    #[ArrayShape([KernelEvents::CONTROLLER_ARGUMENTS => "array", KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['prepareLogging', -10],
            KernelEvents::RESPONSE   => ['ensureLogging', -10],
        ];
    }
}