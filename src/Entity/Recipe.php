<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\RecipeRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'recipe_name_unique', columns: ['name'])]
class Recipe
{
    const WorkshopType = 1;
    const WorkshopTypeShamanSpecific = 2;
    const WorkshopTypeTechSpecific = 3;
    const ManualOutside = 11;
    const ManualInside = 12;
    const ManualAnywhere = 13;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 32)]
    private $name;
    #[ORM\Column(type: 'integer')]
    private $type;
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private $action;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemGroup', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $source;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\ItemPrototype')]
    private $provoking;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemGroup', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $result;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\PictoPrototype')]
    #[ORM\JoinColumn(nullable: true)]
    private $pictoPrototype;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\ItemPrototype')]
    #[ORM\JoinTable(name: 'recipe_keep_item_prototype')]
    private $keep;
    #[ORM\Column(type: 'boolean')]
    private $stealthy = false;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $tooltip_string;

    #[ORM\Column]
    private bool $multiOut = false;

    public function __construct()
    {
        $this->provoking = new ArrayCollection();
        $this->keep = new ArrayCollection();
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
    public function getType(): ?int
    {
        return $this->type;
    }
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
    public function getAction(): ?string
    {
      return $this->action;
    }
    public function setAction(string $action): self
    {
      $this->action = $action;

      return $this;
    }
    public function getSource(): ?ItemGroup
    {
        return $this->source;
    }
    public function setSource(?ItemGroup $source): self
    {
        $this->source = $source;

        return $this;
    }
    /**
     * @return Collection|ItemPrototype[]
     */
    public function getProvoking(): Collection
    {
        return $this->provoking;
    }
    public function addProvoking(ItemPrototype $provoking): self
    {
        if (!$this->provoking->contains($provoking)) {
            $this->provoking[] = $provoking;
        }

        return $this;
    }
    public function removeProvoking(ItemPrototype $provoking): self
    {
        if ($this->provoking->contains($provoking)) {
            $this->provoking->removeElement($provoking);
        }

        return $this;
    }
    public function getResult(): ?ItemGroup
    {
        return $this->result;
    }
    public function setResult(?ItemGroup $result): self
    {
        $this->result = $result;

        return $this;
    }
    public function getPictoPrototype(): ?PictoPrototype
    {
        return $this->pictoPrototype;
    }
    public function setPictoPrototype(?PictoPrototype $pictoPrototype): self
    {
        $this->pictoPrototype = $pictoPrototype;

        return $this;
    }
    /**
     * @return Collection|ItemPrototype[]
     */
    public function getKeep(): Collection
    {
        return $this->keep;
    }
    public function addKeep(ItemPrototype $keep): self
    {
        if (!$this->keep->contains($keep)) {
            $this->keep[] = $keep;
        }

        return $this;
    }
    public function removeKeep(ItemPrototype $keep): self
    {
        if ($this->keep->contains($keep)) {
            $this->keep->removeElement($keep);
        }

        return $this;
    }
    public function getStealthy(): ?bool
    {
        return $this->stealthy;
    }
    public function setStealthy(bool $stealthy): self
    {
        $this->stealthy = $stealthy;

        return $this;
    }
    public function getTooltipString(): ?string
    {
        return $this->tooltip_string;
    }
    public function setTooltipString(?string $tooltip_string): self
    {
        $this->tooltip_string = $tooltip_string;

        return $this;
    }

    public function isMultiOut(): ?bool
    {
        return $this->multiOut;
    }

    public function setMultiOut(bool $multiOut): static
    {
        $this->multiOut = $multiOut;

        return $this;
    }
}
