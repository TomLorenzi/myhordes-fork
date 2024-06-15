<?php

namespace App\Entity;

use App\Repository\HookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HookRepository::class)]
class Hook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $classname = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(length: 255)]
    private ?string $hookname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $funcName = null;

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

    public function getClassname(): ?string
    {
        return $this->classname;
    }

    public function setClassname(string $classname): static
    {
        $this->classname = $classname;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getHookname(): ?string
    {
        return $this->hookname;
    }

    public function setHookname(string $hookname): static
    {
        $this->hookname = $hookname;

        return $this;
    }

    public function getFuncName(): ?string
    {
        return $this->funcName;
    }

    public function setFuncName(?string $funcName): static
    {
        $this->funcName = $funcName;

        return $this;
    }
}
