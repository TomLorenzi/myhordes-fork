<?php

namespace App\Entity;

use App\Repository\SpecialActionPrototypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SpecialActionPrototypeRepository::class)
 */
class SpecialActionPrototype
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=ItemAction::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $action;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $icon;

    /**
     * @ORM\Column(type="boolean")
     */
    private $consumable = true;

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

    public function getConsumable(): ?bool
    {
        return $this->consumable;
    }

    public function setConsumable(bool $consumable): self
    {
        $this->consumable = $consumable;

        return $this;
    }
}
