<?php

namespace App\Entity;

use App\Repository\MayorMarkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MayorMarkRepository::class)]
class MayorMark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expires = null;

    #[ORM\Column]
    private bool $mayor = false;

    #[ORM\Column]
    private bool $citizen = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getExpires(): ?\DateTimeInterface
    {
        return $this->expires;
    }

    public function setExpires(\DateTimeInterface $expires): static
    {
        $this->expires = $expires;

        return $this;
    }

    public function isMayor(): bool
    {
        return $this->mayor;
    }

    public function setMayor(bool $mayor): static
    {
        $this->mayor = $mayor;

        return $this;
    }

    public function isCitizen(): bool
    {
        return $this->citizen;
    }

    public function setCitizen(bool $citizen): static
    {
        $this->citizen = $citizen;

        return $this;
    }
}
