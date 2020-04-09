<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BuildingPrototypeRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="name_unique",columns={"name"})
 * })
 */
class BuildingPrototype
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $label;

    /**
     * @ORM\Column(type="boolean")
     */
    private $temp;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $icon;

    /**
     * @ORM\Column(type="integer")
     */
    private $blueprint;

    /**
     * @ORM\Column(type="integer")
     */
    private $ap;

    /**
     * @ORM\Column(type="integer")
     */
    private $defense;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemGroup", cascade={"persist"})
     */
    private $resources;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BuildingPrototype", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BuildingPrototype", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxLevel = 0;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $upgradeTexts = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $orderBy = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getTemp(): ?bool
    {
        return $this->temp;
    }

    public function setTemp(bool $temp): self
    {
        $this->temp = $temp;

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

    public function getBlueprint(): ?int
    {
        return $this->blueprint;
    }

    public function setBlueprint(int $blueprint): self
    {
        $this->blueprint = $blueprint;

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

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): self
    {
        $this->defense = $defense;

        return $this;
    }

    public function getResources(): ?ItemGroup
    {
        return $this->resources;
    }

    public function setResources(?ItemGroup $ressources): self
    {
        $this->resources = $ressources;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getMaxLevel(): ?int
    {
        return $this->maxLevel;
    }

    public function setMaxLevel(int $maxLevel): self
    {
        $this->maxLevel = $maxLevel;

        return $this;
    }

    public function getUpgradeTexts(): ?array
    {
        return $this->upgradeTexts;
    }

    public function setUpgradeTexts(?array $upgradeTexts): self
    {
        $this->upgradeTexts = $upgradeTexts;

        return $this;
    }

    public function getOrderBy(): ?int
    {
        return $this->orderBy;
    }

    public function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }
}
