<?php


namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\AntiCheatService;
use App\Service\CitizenHandler;
use App\Service\TownHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

use Throwable;

class GameActionTriggerSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;
    private TownHandler $townHandler;
    private AntiCheatService $anti_cheat;
    private CitizenHandler $citizenHandler;

    public function __construct(
        EntityManagerInterface $em, Security $security,
        TownHandler $th, AntiCheatService $anti_cheat, CitizenHandler $ch)
    {
        $this->em = $em;
        $this->security = $security;
        $this->townHandler = $th;
        $this->anti_cheat = $anti_cheat;
        $this->citizenHandler = $ch;
    }

    public function process(ControllerEvent $event) {

        /** @var ?User $user */
        $user = $this->security->getUser();

        if ($user && $event->getRequest()->attributes->get('_domain_recorded', false)) {
            $this->anti_cheat->recordConnection($user, $event->getRequest());
            $this->em->persist( $user->setLastActionTimestamp( new DateTime() ) );
            if ($user->getActiveCitizen()?->getAlive()) {
                $this->em->persist($user->getActiveCitizen()->setLastActionTimestamp(time()));
                $this->citizenHandler->inflictStatus($user->getActiveCitizen(), 'tg_chk_active');
            }
            try { $this->em->flush(); } catch (Throwable) {}
        }

        if ($event->getRequest()->attributes->get('_domain_incarnated', false) && ($citizen = $user?->getActiveCitizen())) {

            $persist = false;
            if ($this->townHandler->triggerAlways( $citizen->getTown() )) {
                $this->em->persist($citizen->getTown());
                $persist = true;
            }

            if ($citizen->getAlive()) {
                $citizen->setLastActionTimestamp(time());
                $this->citizenHandler->inflictStatus($user->getActiveCitizen(), 'tg_chk_active');
                $this->em->persist($citizen);
                $persist = true;
            }

            if ($persist) try { $this->em->flush(); } catch (Throwable) {}
        }

        // Execute before() on HookedControllers
        if ($event->getRequest()->attributes->get('_domain_hooked', false))
            if (!$event->getRequest()->attributes->get('_active_controller', null)?->before()) {
                $event->stopPropagation();
                $event->setController( fn() => new Response(status: 403, headers: ['X-AJAX-Control' => 'reset']) );
            }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['process', -90],
        ];
    }
}