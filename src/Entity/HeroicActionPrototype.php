<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\HeroicActionPrototypeRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'heroic_action_prototype_name_unique', columns: ['name'])]
class HeroicActionPrototype
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
    #[ORM\Column(type: 'boolean')]
    private $unlockable = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $usedMessage = null;
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
    public function getUnlockable(): ?bool
    {
        return $this->unlockable;
    }
    public function setUnlockable(bool $unlockable): self
    {
        $this->unlockable = $unlockable;

        return $this;
    }

    public function getUsedMessage(): ?string
    {
        return $this->usedMessage;
    }

    public function setUsedMessage(?string $usedMessage): self
    {
        $this->usedMessage = $usedMessage;

        return $this;
    }
}
