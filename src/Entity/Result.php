<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\ResultRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'result_name_unique', columns: ['name'])]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $name;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectAP')]
    private $ap;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectStatus')]
    private $status;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectOriginalItem')]
    private $item;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectResultGroup')]
    private $resultGroup;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectBlueprint')]
    private $blueprint;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $custom;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectDeath')]
    private $death;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectOriginalItem')]
    private $target;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectPM')]
    private $pm;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectCP')]
    private $cp;

    #[ORM\Column(nullable: true)]
    private ?array $atoms = null;

    public function __construct()
    {
    }
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
    public function clear(): self {
        $this->ap = $this->status = $this->item = $this->spawn = $this->resultGroup =
        $this->blueprint = $this->custom = $this->death =
        $this->target = $this->pm = $this->cp = $this->atoms = null;
        return $this;
    }
    public function getAp(): ?AffectAP
    {
        return $this->ap;
    }
    public function setAp(?AffectAP $ap): self
    {
        $this->ap = $ap;

        return $this;
    }
    public function getStatus(): ?AffectStatus
    {
        return $this->status;
    }
    public function setStatus(?AffectStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
    public function getItem(): ?AffectOriginalItem
    {
        return $this->item;
    }
    public function setItem(?AffectOriginalItem $item): self
    {
        $this->item = $item;

        return $this;
    }
    public function getResultGroup(): ?AffectResultGroup
    {
        return $this->resultGroup;
    }
    public function setResultGroup(?AffectResultGroup $resultGroup): self
    {
        $this->resultGroup = $resultGroup;

        return $this;
    }
    public function getBlueprint(): ?AffectBlueprint
    {
        return $this->blueprint;
    }
    public function setBlueprint(?AffectBlueprint $blueprint): self
    {
        $this->blueprint = $blueprint;

        return $this;
    }
    public function getCustom(): ?int
    {
        return $this->custom;
    }
    public function setCustom(?int $custom): self
    {
        $this->custom = $custom;

        return $this;
    }
    public function getDeath(): ?AffectDeath
    {
        return $this->death;
    }
    public function setDeath(?AffectDeath $death): self
    {
        $this->death = $death;

        return $this;
    }
    public function getTarget(): ?AffectOriginalItem
    {
        return $this->target;
    }
    public function setTarget(?AffectOriginalItem $target): self
    {
        $this->target = $target;

        return $this;
    }
    public function getPm(): ?AffectPM
    {
        return $this->pm;
    }
    public function setPm(?AffectPM $pm): self
    {
        $this->pm = $pm;

        return $this;
    }
    public function getCp(): ?AffectCP
    {
        return $this->cp;
    }
    public function setCp(?AffectCP $cp): self
    {
        $this->cp = $cp;

        return $this;
    }

    public function getAtoms(): ?array
    {
        return $this->atoms;
    }

    public function setAtoms(?array $atoms): self
    {
        $this->atoms = $atoms;

        return $this;
    }
}
