<?php


namespace App\EventSubscriber;

use App\Annotations\ExternalAPI;
use App\Annotations\GateKeeperProfile;
use App\Entity\ExternalApp;
use App\Entity\User;
use App\Enum\ExternalAPIError;
use App\Service\TimeKeeperService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Security;

class APIRequestListener implements EventSubscriberInterface
{
    private ContainerInterface $container;
    private EntityManagerInterface $entity_manager;
    private Security $security;
    private TimeKeeperService $timeKeeper;

    private RateLimiterFactory $rate_public;
    private RateLimiterFactory $rate_anon;
    private RateLimiterFactory $rate_app;
    private RateLimiterFactory $rate_user;

    private $headers = [];


    public function __construct(
        ContainerInterface $container, EntityManagerInterface $em, Security $security, TimeKeeperService $tk,
        RateLimiterFactory $authenticatedPersonalApiLimiter, RateLimiterFactory $authenticatedApiLimiter,
        RateLimiterFactory $anonymousApiLimiter, RateLimiterFactory $publicApiLimiter,
    )
    {
        $this->container = $container;
        $this->entity_manager = $em;
        $this->security = $security;
        $this->timeKeeper = $tk;

        $this->rate_public = $publicApiLimiter;
        $this->rate_anon = $anonymousApiLimiter;
        $this->rate_app = $authenticatedApiLimiter;
        $this->rate_user = $authenticatedPersonalApiLimiter;
    }

    public function redirectToErrorHandler(ControllerEvent $event, ExternalAPIError $message): int {
        $controller = $event->getController();
        $event->getRequest()->attributes->add( [ 'message' => $message ] );
        $event->setController( [$controller[0], 'on_error'] );
        return 0;
    }

    public function process(ControllerEvent $event) {
        /** @var ?ExternalAPI $apiConf */
        if ($apiConf = $event->getRequest()->attributes->get('_ExternalAPI') ?? null) {

            if (!$event->getRequest()->attributes->get('_GateKeeperProfile')) {
                $gk = new GateKeeperProfile();
                $gk->value = 'skip';
                $event->getRequest()->attributes->add( ['_GateKeeperProfile' => $gk] );
            }



            // Load params
            $app_key = trim($event->getRequest()->query->get('appkey') ?? $event->getRequest()->request->get('appkey') ?? '');
            $user_key = trim($event->getRequest()->query->get('userkey') ?? $event->getRequest()->request->get('userkey') ?? '');
            $language = explode('_', trim($event->getRequest()->query->get('lang') ?? $event->getRequest()->request->get('lang') ?? 'de'))[0];

            $event->getRequest()->attributes->add( [ 'language' => $language ] );

            if ($this->timeKeeper->isDuringAttack())
                return $this->redirectToErrorHandler( $event, ExternalAPIError::HordeAttacking );

            $user = null;
            if (!empty( $user_key )) {
                if ($user_key !== 'fefe0000fefe0000fefe0000fefe0000' || !$apiConf->fefe)
                    $user = $this->entity_manager->getRepository(User::class)->findOneBy(['externalId' => $user_key]);
                else
                    $user = $this->security->getUser();

                if (!$user && $apiConf->user) return $this->redirectToErrorHandler( $event, ExternalAPIError::UserKeyInvalid );
            } elseif ($apiConf->user)
                return $this->redirectToErrorHandler( $event, ExternalAPIError::UserKeyNotFound );

            $event->getRequest()->attributes->add( [ 'user' => $user, 'user_key' => $user ? $user_key : null ] );

            $app = null;
            $fefe_app_key = false;
            if (!empty( $app_key )) {
                if ($app_key !== 'fefe0000fefe0000fefe0000fefe0000' || $user_key !== 'fefe0000fefe0000fefe0000fefe0000' || !$apiConf->fefe) {
                    $app = $this->entity_manager->getRepository(ExternalApp::class)->findOneBy(['secret' => $app_key]);
                    if (!$app && $apiConf->app) return $this->redirectToErrorHandler( $event, ExternalAPIError::AppKeyInvalid );
                } else $fefe_app_key = true;
            } elseif ($apiConf->app)
                return $this->redirectToErrorHandler( $event, ExternalAPIError::AppKeyNotFound );

            $event->getRequest()->attributes->add( [ 'app' => $app, 'app_key' => ($app || $fefe_app_key) ? $app_key : null ] );

            if ($apiConf->cost > 0) {

                $r_app = $event->getRequest()->attributes->get( 'app_key',  null );
                $r_usr = $event->getRequest()->attributes->get( 'user_key', null );

                $limiters = [];
                if (!$r_usr && !$r_app) $limiters = [$this->rate_public->create('pbk')];
                elseif ($r_usr && !$r_app) $limiters = [$this->rate_anon->create(  $user?->getId() ?? $r_usr )];
                elseif (!$r_usr && $r_app) $limiters = [$this->rate_app->create( $app?->getId() ?? $r_app )];
                elseif ($r_usr && $r_app) $limiters = [$this->rate_app->create( $app?->getId() ?? $r_app ), $this->rate_user->create( $user?->getId() ?? $r_usr )];

                $this->headers = [
                    'X-RateLimit-Remaining' => PHP_INT_MAX,
                    'X-RateLimit-Retry-After' => 0,
                    'X-RateLimit-Limit' => PHP_INT_MAX,
                ];

                /** @var LimiterInterface $limiter */
                foreach ( $limiters as $limiter ) {

                    $limit = $limiter->consume( $apiConf->cost );

                    $this->headers['X-RateLimit-Remaining'] = min( $this->headers['X-RateLimit-Remaining'], $limit->getRemainingTokens() );
                    $this->headers['X-RateLimit-Retry-After'] = max( $this->headers['X-RateLimit-Retry-After'], $limit->getRetryAfter()->getTimestamp() );
                    $this->headers['X-RateLimit-Limit'] = min( $this->headers['X-RateLimit-Limit'], $limit->getLimit() );

                    if (!$limit->isAccepted())
                        throw new TooManyRequestsHttpException($limit->getRetryAfter()->format("Y-m-d H:i:s"), "Rate Limit Exceeded", null, 0, $this->headers);
                }
            }

            //var_dump( $event->getRequest()->attributes->keys() ); die;
        }

        return 0;
    }

    public function addRateLimiterTokens(ResponseEvent $event) {
        $event->getResponse()->headers->add($this->headers);
    }

    /**
     * @inheritDoc
     */
    #[ArrayShape([KernelEvents::CONTROLLER => "array", KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['process', -1],
            KernelEvents::RESPONSE   => ['addRateLimiterTokens', -1],
        ];
    }
}