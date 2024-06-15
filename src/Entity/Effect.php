<?php

namespace App\Entity;

use App\Repository\EffectRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EffectRepository::class)]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'effect_name_unique', columns: ['name'])]
class Effect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $name = null;

    #[ORM\Column]
    private array $atoms = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAtoms(): array
    {
        return $this->atoms;
    }

    public function setAtoms(array $atoms): static
    {
        $this->atoms = $atoms;

        return $this;
    }
}
