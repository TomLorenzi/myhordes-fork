<?php

namespace App\Entity;

use App\Repository\UserSwapPivotRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserSwapPivotRepository::class)]
#[UniqueConstraint(name: 'user_swap_pivot_definition', columns: ['principal_id','secondary_id'])]
class UserSwapPivot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue("CUSTOM")]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $principal = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $secondary = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPrincipal(): ?User
    {
        return $this->principal;
    }

    public function setPrincipal(?User $principal): self
    {
        $this->principal = $principal;

        return $this;
    }

    public function getSecondary(): ?User
    {
        return $this->secondary;
    }

    public function setSecondary(?User $secondary): self
    {
        $this->secondary = $secondary;

        return $this;
    }
}
