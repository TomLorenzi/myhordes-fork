<?php


namespace App\EventSubscriber;


use App\Controller\BeyondInterfaceController;
use App\Controller\ExternalController;
use App\Controller\GameAliveInterfaceController;
use App\Controller\GameInterfaceController;
use App\Controller\GameProfessionInterfaceController;
use App\Controller\GhostInterfaceController;
use App\Controller\LandingController;
use App\Controller\TownInterfaceController;
use App\Controller\WebController;
use App\Entity\Citizen;
use App\Entity\User;
use App\Exception\DynamicAjaxResetException;
use App\Service\Locksmith;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Proxies\__CG__\App\Entity\CitizenProfession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

class GateKeeperSubscriber implements EventSubscriberInterface
{
    private $security;
    private $em;
    private $locksmith;
    private $townHandler;
    private $timeKeeper;

    /** @var LockInterface|null  */
    private $current_lock = null;

    public function __construct(EntityManagerInterface $em, Locksmith $locksmith, Security $security, TownHandler $th, TimeKeeperService $tk)
    {
        $this->em = $em;
        $this->locksmith = $locksmith;
        $this->security = $security;
        $this->townHandler = $th;
        $this->timeKeeper = $tk;
    }

    public function holdTheDoor(ControllerEvent $event) {
        $controller = $event->getController();
        if (is_array($controller)) $controller = $controller[0];

        if (!($controller instanceof LandingController) && !($controller instanceof WebController)) {
            // During the attack, only the landing and web controller shall be made available
            if ($this->timeKeeper->isDuringAttack())
                throw new DynamicAjaxResetException($event->getRequest());
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if ($user && $user->getLanguage() && $event->getRequest()->getLocale() !== $user->getLanguage())
            $event->getRequest()->getSession()->set('_user_lang', $user->getLanguage());

        if ($controller instanceof GhostInterfaceController) {
            // This is a ghost controller; it is not available to players in a game
            if (!$user || $user->getActiveCitizen())
                throw new DynamicAjaxResetException($event->getRequest());
        }

        if ($controller instanceof GameInterfaceController && !($controller instanceof ExternalController)) {
            // This is a game controller; it is not available to players outside of a game
            if (!$user || !$citizen = $user->getActiveCitizen())
                throw new DynamicAjaxResetException($event->getRequest());

            /** @var $citizen Citizen */
            $this->current_lock = $this->locksmith->waitForLock( 'game-' . $citizen->getTown()->getId() );
            $this->townHandler->triggerAlways( $citizen->getTown(), true );

            if ($controller instanceof GameAliveInterfaceController) {
                // This is a game action controller; it is not available to players who are dead
                if (!$citizen->getAlive())
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            if ($controller instanceof GameProfessionInterfaceController) {
                // This is a game profession controller; it is not available to players who have not chosen a profession
                // yet.
                if ($citizen->getProfession()->getName() === CitizenProfession::DEFAULT)
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            if ($controller instanceof TownInterfaceController) {
                // This is a town controller; it is not available to players in the world beyond
                if ($citizen->getZone())
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            if ($controller instanceof BeyondInterfaceController) {
                // This is a beyond controller; it is not available to players inside a town
                if (!$citizen->getZone())
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            $citizen->setLastActionTimestamp(time());

            $this->em->persist($citizen);
            $this->em->flush();
        }
    }

    public function releaseTheDoor(ResponseEvent $event) {
        if ($this->current_lock) $this->current_lock->release();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'holdTheDoor',
            KernelEvents::RESPONSE   => 'releaseTheDoor',
        ];
    }
}