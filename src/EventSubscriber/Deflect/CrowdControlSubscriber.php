<?php


namespace App\EventSubscriber\Deflect;

use App\Annotations\GateKeeperProfile;
use App\Entity\CitizenProfession;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Prevents invalid actions based on the current gameplay state, such as being in town or exploring a ruin.
 */
class CrowdControlSubscriber extends DeflectorCore
{
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator
    ){ }

    const PRIORITY = -80;

    protected function handle(GateKeeperProfile $gateKeeperProfile): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($gateKeeperProfile->onlyWhenGhost() && (!$user || $user->getActiveCitizen())) {
            // This is a ghost controller; it is not available to players in a game
            $this->ajaxReset();
            return;
        }

        if ($gateKeeperProfile->onlyWhenIncarnated()) {
            // This is a game controller; it is not available to players outside a game
            if (!($citizen = $user?->getActiveCitizen())) {
                $this->ajaxReset();
                return;
            }

            if ($gateKeeperProfile->onlyWhenAlive() && !$citizen->getAlive()) {
                // This is a game action controller; it is not available to players who are dead
                $this->ajaxReset();
                return;
            }

            if ($gateKeeperProfile->onlyWithProfession() && $citizen->getProfession()->getName() === CitizenProfession::DEFAULT) {
                // This is a game profession controller; it is not available to players who have not chosen a profession
                // yet.
                $this->ajaxReset();
                return;
            }

            if ($gateKeeperProfile->onlyInTown() && $citizen->getZone()) {
                // This is a town controller; it is not available to players in the world beyond
                if ($this->event->getRequest()->headers->get('X-Request-Intent', 'UndefinedIntent') !== 'WebNavigation')
                    $this->event->getRequest()->getSession()->getFlashBag()->add("error", $this->translator->trans("HINWEIS: Diese Aktion ist nur in der Stadt möglich.", [], 'global'));
                $this->ajaxReset();
                return;
            }

            if ($gateKeeperProfile->onlyBeyond()) {
                // This is a beyond controller; it is not available to players inside a town
                if (!$citizen->getZone()) {
                    if ($this->event->getRequest()->headers->get('X-Request-Intent', 'UndefinedIntent') !== 'WebNavigation')
                        $this->event->getRequest()->getSession()->getFlashBag()->add("error", $this->translator->trans("HINWEIS: Diese Aktion ist nur in Übersee möglich.", [], 'global'));
                    $this->ajaxReset();
                    return;
                }

                // Check if the exploration status is set
                if ($gateKeeperProfile->onlyInRuin() xor $citizen->activeExplorerStats()) {
                    $this->ajaxReset();
                    return;
                }
            }
        }
    }
}