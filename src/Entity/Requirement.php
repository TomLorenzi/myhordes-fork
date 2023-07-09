<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\RequirementRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'requirement_name_unique', columns: ['name'])]
class Requirement
{
    const HideOnFail  = 0;
    const CrossOnFail = 1;
    const MessageOnFail = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $name;
    #[ORM\Column(type: 'smallint')]
    private $failureMode;
    #[ORM\Column(type: 'text', nullable: true)]
    private $failureText;

    #[ORM\Column(nullable: true)]
    private ?array $atoms = null;

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
        $this->atoms = null;
        return $this;
    }
    public function getFailureMode(): ?int
    {
        return $this->failureMode;
    }
    public function setFailureMode(int $failureMode): self
    {
        $this->failureMode = $failureMode;

        return $this;
    }
    public function getFailureText(): ?string
    {
        return $this->failureText;
    }
    public function setFailureText(?string $failureText): self
    {
        $this->failureText = $failureText;

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
