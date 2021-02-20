<?php


namespace App\EventSubscriber;

use App\Controller\HookedInterfaceController;
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
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Annotations\GateKeeperProfile;

class GateKeeperSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;
    private Locksmith $locksmith;
    private TownHandler $townHandler;
    private TimeKeeperService $timeKeeper;
    private AntiCheatService $anti_cheat;
    private UrlGeneratorInterface $url_generator;
    private TranslatorInterface $translator;

    /** @var LockInterface|null  */
    private $current_lock = null;

    public function __construct(
        EntityManagerInterface $em, Locksmith $locksmith, Security $security,
        TownHandler $th, TimeKeeperService $tk, AntiCheatService $anti_cheat, UrlGeneratorInterface $url, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->locksmith = $locksmith;
        $this->security = $security;
        $this->townHandler = $th;
        $this->timeKeeper = $tk;
        $this->anti_cheat = $anti_cheat;
        $this->url_generator = $url;
        $this->translator = $translator;
    }

    public function holdTheDoor(ControllerEvent $event) {

        $gk_profile = $event->getRequest()->attributes->get('_GateKeeperProfile') ?? new GateKeeperProfile();
        if ($gk_profile->skipGateKeeper() || $event->getRequest()->attributes->get('_debug_skip_gk')) return;

        $controller = $event->getController();
        if (is_array($controller)) $controller = $controller[0];

        // During the attack, only whitelisted controllers and functions are available
        // This is controlled by the allow_during_attack parameter of @GateKeeperProfile
        if (!$gk_profile->getAllowDuringAttack() && $this->timeKeeper->isDuringAttack())
            throw new DynamicAjaxResetException($event->getRequest());

        /** @var User $user */
        $user = $this->security->getUser();
        if ($gk_profile->getRecordUserActivity() && $user) {
            $this->anti_cheat->recordConnection($user, $event->getRequest());
            $this->em->persist( $user->setLastActionTimestamp( new \DateTime() ) );
            try { $this->em->flush(); } catch (Exception $e) {}
        }

        if ($user && $user->getLanguage() && $event->getRequest()->getLocale() !== $user->getLanguage())
            $event->getRequest()->getSession()->set('_user_lang', $user->getLanguage());

        if ($gk_profile->onlyWhenGhost() && (!$user || $user->getActiveCitizen()))
            // This is a ghost controller; it is not available to players in a game
            throw new DynamicAjaxResetException($event->getRequest());

        if ($gk_profile->onlyWhenIncarnated()) {
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

            if ($gk_profile->onlyWhenAlive() && !$citizen->getAlive())
                // This is a game action controller; it is not available to players who are dead
                throw new DynamicAjaxResetException($event->getRequest());

            if ($gk_profile->onlyWithProfession() && $citizen->getProfession()->getName() === CitizenProfession::DEFAULT) {
                // This is a game profession controller; it is not available to players who have not chosen a profession
                // yet.
                throw new DynamicAjaxResetException($event->getRequest());
            }

            if ($gk_profile->onlyInTown() && $citizen->getZone()) {
                // This is a town controller; it is not available to players in the world beyond
                if ($event->getRequest()->headers->get('X-Requested-With', 'UndefinedIntent') !== 'WebNavigation')
                    $event->getRequest()->getSession()->getFlashBag()->add("error", $this->translator->trans("HINWEIS: Diese Aktion ist nur in der Stadt möglich.", [], 'global'));
                throw new DynamicAjaxResetException($event->getRequest());
            }

            if ($gk_profile->onlyBeyond()) {
                // This is a beyond controller; it is not available to players inside a town
                if (!$citizen->getZone()) {
                    if ($event->getRequest()->headers->get('X-Requested-With', 'UndefinedIntent') !== 'WebNavigation')
                        $event->getRequest()->getSession()->getFlashBag()->add("error", $this->translator->trans("HINWEIS: Diese Aktion ist nur in Übersee möglich.", [], 'global'));
                    throw new DynamicAjaxResetException($event->getRequest());
                }

                // Check if the exploration status is set
                if ($gk_profile->onlyInRuin() xor $citizen->activeExplorerStats())
                    throw new DynamicAjaxResetException($event->getRequest());
            }

            $citizen->setLastActionTimestamp(time());
            $this->em->persist($citizen);

            $this->em->flush();

            // Execute before() on HookedControllers
            if ($gk_profile->hasHook() && $controller instanceof HookedInterfaceController)
                if (!$controller->before())
                    throw new DynamicAjaxResetException($event->getRequest());
        }
    }

    public function releaseTheDoor(ResponseEvent $event) {
        if ($this->current_lock) $this->current_lock->release();
    }

    public function removeRememberMeToken(LogoutEvent $event) {
        $event->getResponse()->headers->clearCookie('myhordes_remember_me');
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['holdTheDoor', -1],
            KernelEvents::RESPONSE   => 'releaseTheDoor',
            LogoutEvent::class => ['removeRememberMeToken',-1],
        ];
    }
}