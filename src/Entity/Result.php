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
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectResultGroup')]
    private $resultGroup;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $custom;

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
        $this->resultGroup = $this->custom = $this->atoms = null;
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
    public function getCustom(): ?int
    {
        return $this->custom;
    }
    public function setCustom(?int $custom): self
    {
        $this->custom = $custom;

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
