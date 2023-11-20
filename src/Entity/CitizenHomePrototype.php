<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenHomePrototypeRepository')]
#[UniqueEntity('level')]
#[Table]
#[UniqueConstraint(name: 'level_unique', columns: ['level'])]
class CitizenHomePrototype
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $level;
    #[ORM\Column(type: 'string', length: 32)]
    private $label;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
    #[ORM\Column(type: 'integer')]
    private $defense;
    #[ORM\Column(type: 'integer')]
    private $ap;
    #[ORM\Column(type: 'integer')]
    private $apUrbanism;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemGroup', cascade: ['persist'])]
    private $resources;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemGroup', cascade: ['persist'])]
    private $resourcesUrbanism;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\BuildingPrototype')]
    private $requiredBuilding;
    #[ORM\Column(type: 'boolean')]
    private $allowSubUpgrades;
    #[ORM\Column(type: 'boolean')]
    private $theftProtection;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

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
    public function getDefense(): ?int
    {
        return $this->defense;
    }
    public function setDefense(int $defense): self
    {
        $this->defense = $defense;

        return $this;
    }
    public function getAp(): ?int
    {
        return $this->ap;
    }
    public function setAp(int $ap): self
    {
        $this->ap = $ap;

        return $this;
    }
    public function getApUrbanism(): ?int
    {
        return $this->apUrbanism;
    }
    public function setApUrbanism(int $apUrbanism): self
    {
        $this->apUrbanism = $apUrbanism;

        return $this;
    }
    public function getResources(): ?ItemGroup
    {
        return $this->resources;
    }
    public function setResources(?ItemGroup $resources): self
    {
        $this->resources = $resources;

        return $this;
    }
    public function getResourcesUrbanism(): ?ItemGroup
    {
        return $this->resourcesUrbanism;
    }
    public function setResourcesUrbanism(?ItemGroup $resourcesUrbanism): self
    {
        $this->resourcesUrbanism = $resourcesUrbanism;

        return $this;
    }
    public function getRequiredBuilding(): ?BuildingPrototype
    {
        return $this->requiredBuilding;
    }
    public function setRequiredBuilding(?BuildingPrototype $requiredBuilding): self
    {
        $this->requiredBuilding = $requiredBuilding;

        return $this;
    }
    public function getAllowSubUpgrades(): ?bool
    {
        return $this->allowSubUpgrades;
    }
    public function setAllowSubUpgrades(bool $allowSubUpgrades): self
    {
        $this->allowSubUpgrades = $allowSubUpgrades;

        return $this;
    }
    public function getTheftProtection(): ?bool
    {
        return $this->theftProtection;
    }
    public function setTheftProtection(bool $theftProtection): self
    {
        $this->theftProtection = $theftProtection;

        return $this;
    }
}
