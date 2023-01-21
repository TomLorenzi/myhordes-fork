<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\HomeActionPrototypeRepository')]
#[UniqueEntity('name')]
#[UniqueConstraint(name: 'home_action_prototype_unique', columns: ['name'])]
class HomeActionPrototype
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $name;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemAction')]
    #[ORM\JoinColumn(nullable: false)]
    private $action;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
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
    public function getAction(): ?ItemAction
    {
        return $this->action;
    }
    public function setAction(?ItemAction $action): self
    {
        $this->action = $action;

        return $this;
    }
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}
