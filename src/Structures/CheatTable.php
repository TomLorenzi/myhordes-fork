<?php


namespace App\Structures;

use App\Entity\User;

class CheatTable
{
    private $principal;
    private $users;
    private $likeliness;

    public function __construct(User $principal, int $likeliness = 0)
    {
        $this->principal = $principal;
        $this->likeliness = $likeliness;
    }

    public function addUser(User $user): self {
        $this->users[$user->getId()] = $user;
        return $this;
    }

    public function addLikeliness(int $likeliness): self {
        $this->likeliness += $likeliness;
        return $this;
    }

    public function getPrincipal(): User {
        return $this->principal;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array {
        return array_values($this->users);
    }

    public function getLikeliness(): int {
        return $this->likeliness;
    }
}