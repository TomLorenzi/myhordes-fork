<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\AffectStatusRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'affect_status_name_unique', columns: ['name'])]
class AffectStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 64)]
    private $name;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\CitizenStatus')]
    private $initial;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\CitizenStatus')]
    private $result;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $resetThirstCounter;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $counter;
    #[ORM\ManyToOne(targetEntity: CitizenRole::class)]
    private $role;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $roleAdd;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $citizenHunger;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $forced;

    #[ORM\Column(nullable: true)]
    private ?int $probability = null;

    #[ORM\Column]
    private bool $modifyProbability = true;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getInitial(): ?CitizenStatus
    {
        return $this->initial;
    }
    public function setInitial(?CitizenStatus $initial): self
    {
        $this->initial = $initial;

        return $this;
    }
    public function getResult(): ?CitizenStatus
    {
        return $this->result;
    }
    public function setResult(?CitizenStatus $result): self
    {
        $this->result = $result;

        return $this;
    }
    public function getResetThirstCounter(): ?bool
    {
        return $this->resetThirstCounter;
    }
    public function setResetThirstCounter(?bool $resetThirstCounter): self
    {
        $this->resetThirstCounter = $resetThirstCounter;

        return $this;
    }
    public function getCounter(): ?int
    {
        return $this->counter;
    }
    public function setCounter(?int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }
    public function getRole(): ?CitizenRole
    {
        return $this->role;
    }
    public function setRole(?CitizenRole $role): self
    {
        $this->role = $role;

        return $this;
    }
    public function getRoleAdd(): ?bool
    {
        return $this->roleAdd;
    }
    public function setRoleAdd(?bool $roleAdd): self
    {
        $this->roleAdd = $roleAdd;

        return $this;
    }
    public function getCitizenHunger(): ?int
    {
        return $this->citizenHunger;
    }
    public function setCitizenHunger(?int $citizenHunger): self
    {
        $this->citizenHunger = $citizenHunger;

        return $this;
    }
    public function getForced(): ?bool
    {
        return $this->forced;
    }
    public function setForced(?bool $forced): self
    {
        $this->forced = $forced;

        return $this;
    }

    public function getProbability(): ?int
    {
        return $this->probability;
    }

    public function setProbability(?int $probability): static
    {
        $this->probability = $probability;

        return $this;
    }

    public function isModifyProbability(): ?bool
    {
        return $this->modifyProbability;
    }

    public function setModifyProbability(bool $modifyProbability): static
    {
        $this->modifyProbability = $modifyProbability;

        return $this;
    }
}
