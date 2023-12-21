<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\Town;
use App\Entity\User;
use App\Event\Game\GameEvent;
use App\Event\Game\GameInteractionEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class EventFactory implements ServiceSubscriberInterface
{

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            Security::class,
            ConfMaster::class
        ];
    }

    /**
     * Instances a game interaction event object
     *
     * @param string $class The name of the event class.
     * @psalm-param class-string<T> $class
     *
     * @return GameInteractionEvent The repository class.
     * @psalm-return T
     *
     * @template T as GameInteractionEvent
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function gameInteractionEvent(string $class, ?Citizen $citizen = null): GameInteractionEvent {
        $conf = $this->container->get(ConfMaster::class);
        $citizen ??= $this->container->get(Security::class)->getUser()->getActiveCitizen();

        return new $class(
            $citizen,
            $conf->getTownConfiguration( $citizen->getTown() ),
            $conf->getGlobalConf(),
        );
    }

    /**
     * Instances a game event object
     *
     * @param string $class The name of the event class.
     * @psalm-param class-string<T> $class
     *
     * @return GameEvent The repository class.
     * @psalm-return T
     *
     * @template T as GameEvent
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function gameEvent(string $class, Town $town): GameEvent {
        $conf = $this->container->get(ConfMaster::class);

        return new $class(
            $town,
            $conf->getTownConfiguration( $town ),
            $conf->getGlobalConf(),
        );
    }
}