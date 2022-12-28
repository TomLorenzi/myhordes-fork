<?php


namespace App\EventSubscriber\Deflect;

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use App\Annotations\GateKeeperProfile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class DeflectorCore implements EventSubscriberInterface
{

    protected ?ControllerEvent $event = null;

    const PRIORITY = 0;

    /**
     * Stops the event propagation and sends a response immediately without invoking the actual target controller
     * @param int $status HTTP status code
     * @param string|null $message Content of the HTTP response (empty by default)
     * @param array|null $headers Headers to send
     * @param string|null $ajaxControl When given, will be transmitted as X-AJAX-Control header. Overwrites any pre-existing headers of the same name.
     * @return void
     */
    protected function terminateRequestEarly(int $status = 200, ?string $message = null, ?array $headers = [], ?string $ajaxControl = null): void {
        $this->event->stopPropagation();
        if ($ajaxControl !== null) $headers['X-AJAX-Control'] = $ajaxControl;
        $this->event->setController( fn() => new Response($message, $status, $headers) );
    }

    protected function redirectRequest(UrlGeneratorInterface $generator, string $route): void {
        $this->event->stopPropagation();
        $this->event->setController( fn() => (new RedirectController($generator))->redirectAction($this->event->getRequest(), $route) );
    }

    /**
     * Stops event propagation, cancels invocation of the target controller and sends an X-AJAX-CONTROL reset
     * @return void
     */
    protected function ajaxReset(): void {
        $this->terminateRequestEarly(status: 403, ajaxControl: 'reset');
    }

    abstract protected function handle(GateKeeperProfile $gateKeeperProfile): void;

    final public function deflect(ControllerEvent $event) {
        $gk_profile = $event->getRequest()->attributes->get('_GateKeeperProfile') ?? null;
        if (!$gk_profile) return;

        $this->event = $event;
        $this->handle($gk_profile);
    }

    /**
     * @inheritDoc
     */
    final public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['deflect', static::PRIORITY]
        ];
    }
}