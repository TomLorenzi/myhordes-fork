<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserSwapPivot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ImpersonationVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) { }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute == 'CAN_IMPERSONATE' && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous or if the subject is not a user, do not grant access
        if (!$user instanceof UserInterface || !$subject instanceof UserInterface)
            return false;

        if ($this->security->isGranted('ROLE_ADMIN'))
            return true;

        $pivot = $this->entityManager->getRepository(UserSwapPivot::class)->findOneBy(['principal' => $user, 'secondary' => $subject]);
        if ($pivot)
            return true;

        return false;
    }
}
