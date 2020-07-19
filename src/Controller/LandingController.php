<?php

namespace App\Controller;

use App\Entity\Changelog;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\User;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingController extends AbstractController
{

    /**
     * @Route("jx/landing", name="initial_landing",condition="request.isXmlHttpRequest()")
     * @param EntityManagerInterface $em
     * @param TimeKeeperService $tk
     * @return Response
     */
    public function main_landing(EntityManagerInterface $em, TimeKeeperService $tk, Request $request, UserHandler $userHandler): Response
    {
        if ($tk->isDuringAttack())
            return $this->redirect($this->generateUrl('maintenance_attack'));

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user)
            return $this->redirect($this->generateUrl('public_welcome'));
        elseif (!$user->getValidated())
            return $this->redirect($this->generateUrl('public_validate'));
        elseif ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl('soul_death'));
        else {
            // The user is properly authenticated has no pending death pages to confirm
            // Check if there is some news for him to see
            if (!$userHandler->hasSeenLatestChangelog($user, $request->getLocale()))
                return $this->redirect($this->generateUrl('soul_news'));
            elseif ($em->getRepository(Citizen::class)->findActiveByUser($user))
                return $this->redirect($this->generateUrl('game_landing'));
            else
                return $this->redirect($this->generateUrl('ghost_welcome'));
        }

    }

    /**
     * @Route("jx/offline/attack_processing", name="maintenance_attack",condition="request.isXmlHttpRequest()")
     * @param EntityManagerInterface $em
     * @param TimeKeeperService $tk
     * @return Response
     */
    public function maintenance_attack(EntityManagerInterface $em, TimeKeeperService $tk, RandomGenerator $rand): Response
    {
        if (!$tk->isDuringAttack())
            return $this->redirect($this->generateUrl('initial_landing'));

        return $this->render( 'ajax/public/maintenance_attack.html.twig', ['rand' => $rand] );
    }


}
