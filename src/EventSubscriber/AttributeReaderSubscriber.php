<?php


namespace App\EventSubscriber;

use App\Annotations\CustomAttribute;
use App\Annotations\Toaster;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class AttributeReaderSubscriber implements EventSubscriberInterface
{

    public function onKernelController(KernelEvent $event) {

        $controller = $event->getController();
        if (!is_array($controller) && method_exists($controller, '__invoke'))
            $controller = [$controller, '__invoke'];

        if (!is_array($controller)) return;

        $controller_class = new ReflectionClass( get_class( $controller[0] ) );
        $controller_method = $controller_class->getMethod( $controller[1] );

        $cache = [];

        array_map(function (ReflectionAttribute $attribute) use (&$cache) {
            /** @var CustomAttribute $instance */
            $instance = $attribute->newInstance();
            $name = $instance::getAliasName();

            if ($instance::isRepeatable()) {
                if (isset($cache[$name])) $cache[$name] = [];
                $cache[$name][] = $instance;
            } else $cache[$name] = $instance;
        }, array_merge(
            $controller_class->getAttributes( CustomAttribute::class, ReflectionAttribute::IS_INSTANCEOF ),
            $controller_method->getAttributes( CustomAttribute::class, ReflectionAttribute::IS_INSTANCEOF ),
        ));

        $request = $event->getRequest();
        foreach ($cache as $key => $value)
            $request->attributes->set("_{$key}", $value);

        //dump(get_class( $controller[0] ), $controller[1], $cache);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}