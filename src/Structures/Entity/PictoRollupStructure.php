<?php


namespace App\Structures\Entity;

use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Interfaces\Entity\PictoRollupInterface;

readonly class PictoRollupStructure implements PictoRollupInterface
{

    public function __construct(
        private PictoPrototype $prototype,
        private User $user,
        private int $count
    ) { }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getPrototype(): ?PictoPrototype
    {
        return $this->prototype;
    }
}