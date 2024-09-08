<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\RuinZonePrototypeRepository')]
class RuinZonePrototype
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;
    #[ORM\Column(type: 'string', length: 190)]
    private ?string $label = null;
    #[ORM\ManyToOne]
    private ?ItemPrototype $keyImprint;
    #[ORM\ManyToOne]
    private ?ItemPrototype $keyItem;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\ManyToOne]
    private ?ItemPrototype $keyImprintAlternative = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getKeyImprint(): ?ItemPrototype
    {
        return $this->keyImprint;
    }
    public function setKeyImprint(?ItemPrototype $keyImprint): self
    {
        $this->keyImprint = $keyImprint;

        return $this;
    }
    public function getKeyItem(): ?ItemPrototype
    {
        return $this->keyItem;
    }
    public function setKeyItem(?ItemPrototype $keyItem): self
    {
        $this->keyItem = $keyItem;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getKeyImprintAlternative(): ?ItemPrototype
    {
        return $this->keyImprintAlternative;
    }

    public function setKeyImprintAlternative(?ItemPrototype $keyImprintAlternative): static
    {
        $this->keyImprintAlternative = $keyImprintAlternative;

        return $this;
    }
}
