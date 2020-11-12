<?php


namespace App\EventSubscriber;


use App\Controller\Admin\AdminActionController;
use App\Controller\BeyondController;
use App\Controller\BeyondInterfaceController;
use App\Controller\ExplorationInterfaceController;
use App\Controller\ExternalController;
use App\Controller\GameAliveInterfaceController;
use App\Controller\GameInterfaceController;
use App\Controller\GameProfessionInterfaceController;
use App\Controller\GhostInterfaceController;
use App\Controller\HookedInterfaceController;
use App\Controller\LandingController;
use App\Controller\TownInterfaceController;
use App\Controller\WebController;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\User;
use App\Exception\DynamicAjaxResetException;
use App\Service\AntiCheatService;
use App\Service\Locksmith;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class GateKeeperSubscriber implements EventSubscriberInterface
{
    private $security;
    private $em;
    private $locksmith;
    private $townHandler;
    private $timeKeeper;
    private $anti_cheat;
    private $url_generator;

    /** @var LockInterface|null  */
    private $current_lock = null;

    public function __construct(EntityManagerInterface $em, Locksmith $locksmith, Security $security, TownHandler $th, TimeKeeperService $tk, AntiCheatService $anti_cheat, UrlGeneratorInterface $url)
    {
        $this->em = $em;
        $this->locksmith = $locksmith;
        $this->security = $security;
        $this->townHandler = $th;
        $this->timeKeeper = $tk;
        $this->anti_cheat = $anti_cheat;
        $this->url_generator = $url;
    }

    public function holdTheDoor(ControllerEvent $event) {
        $controller = $event->getController();
        if (is_array($controller)) $controller = $controller[0];

        if (!($controller instanceof LandingController) && !($controller instanceof WebController) && !($controller instanceof AdminActionController) && !($controller instanceof ExternalController)) {
            // During the attack, only the landing, web and admin controller shall be made available
            if ($this->timeKeeper->isDuringAttack())
                throw new DynamicAjaxResetException($event->getRequest());
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if ($user) {
            $this->anti_cheat->recordConnection($user, $event->getRequest());
            $this->em->persist( $user->setLastActionTimestamp( new \DateTime() ) );
            try { $this->em->flush(); } catch (Exception $e) {}
        }

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

            // Redirect shadow-banned users
            if ($user->getShadowBan()) {
                $event->setController(function() use ($event) { return (new RedirectController($this->url_generator))->redirectAction($event->getRequest(), 'soul_disabled'); });
                return;
            }

            /** @var $citizen Citizen */
            $this->current_lock = $this->locksmith->waitForLock( 'game-' . $citizen->getTown()->getId() );
            if ($this->townHandler->triggerAlways( $citizen->getTown() ))
                $this->em->persist( $citizen->getTown() );

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

            if ($controller instanceof BeyondInterfaceController && !($controller instanceof ExplorationInterfaceController)) {
                // This is a beyond controller; it is not available to players inside a town
                if (!$citizen->getZone())
                    throw new DynamicAjaxResetException($event->getRequest());

                // Check if the exploration status is set
                if ($controller instanceof ExplorationInterfaceController xor $citizen->activeExplorerStats())
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            $citizen->setLastActionTimestamp(time());
            $this->em->persist($citizen);

            $this->em->flush();

            // Execute before() on HookedControllers
            if ($controller instanceof HookedInterfaceController)
                if (!$controller->before())
                    throw new DynamicAjaxResetException($event->getRequest());
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