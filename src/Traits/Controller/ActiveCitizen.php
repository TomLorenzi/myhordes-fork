<?php

namespace App\Traits\Controller;

use App\Entity\Citizen;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

trait ActiveCitizen
{
    abstract function getUser(): User|UserInterface|null;

    private ?Citizen $cache_active_citizen = null;

    /**
     * @return Citizen|null The current citizen for the current user
     */
    protected function getActiveCitizen(): ?Citizen {
        $user = $this->getUser();
        return $user
            ? $this->cache_active_citizen ?? ($this->cache_active_citizen = $user->getActiveCitizen())
            : null;
    }
}