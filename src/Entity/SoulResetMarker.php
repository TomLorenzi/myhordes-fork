<?php

namespace App\Entity;

use App\Repository\SoulResetMarkerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoulResetMarkerRepository::class)]
class SoulResetMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\OneToOne(targetEntity: CitizenRankingProxy::class, inversedBy: 'resetMarker')]
    #[ORM\JoinColumn(nullable: false)]
    private $ranking;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getRanking(): ?CitizenRankingProxy
    {
        return $this->ranking;
    }
    public function setRanking(CitizenRankingProxy $ranking): self
    {
        $this->ranking = $ranking;

        return $this;
    }
}
