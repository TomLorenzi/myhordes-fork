<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenStatusRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'citizen_status_name_unique', columns: ['name'])]
class CitizenStatus implements NamedEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 16)]
    private $name;
    #[ORM\Column(type: 'string', length: 190)]
    private $label;
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;
    #[ORM\Column(type: 'boolean')]
    private $hidden = false;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
    #[ORM\Column(type: 'integer')]
    private $nightWatchDefenseBonus;
    #[ORM\Column(type: 'float')]
    private $nightWatchDeathChancePenalty;

    #[ORM\Column]
    private ?bool $volatile = false;
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
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

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
    public function getNightWatchDefenseBonus(): ?int
    {
        return $this->nightWatchDefenseBonus;
    }
    public function setNightWatchDefenseBonus(int $nightWatchDefenseBonus): self
    {
        $this->nightWatchDefenseBonus = $nightWatchDefenseBonus;

        return $this;
    }
    public function getNightWatchDeathChancePenalty(): ?float
    {
        return $this->nightWatchDeathChancePenalty;
    }
    public function setNightWatchDeathChancePenalty(float $nightWatchDeathChancePenalty): self
    {
        $this->nightWatchDeathChancePenalty = $nightWatchDeathChancePenalty;

        return $this;
    }
    public static function getTranslationDomain(): ?string
    {
        return 'game';
    }

    public function isVolatile(): ?bool
    {
        return $this->volatile;
    }

    public function setVolatile(bool $volatile): static
    {
        $this->volatile = $volatile;

        return $this;
    }
}
