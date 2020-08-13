<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemCategoryRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="item_category_name_unique",columns={"name"})
 * })
 */
class ItemCategory implements NamedEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemCategory", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ItemCategory", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\Column(type="smallint")
     */
    private $ordering;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ItemPrototype", mappedBy="category")
     */
    private $itemPrototypes;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->itemPrototypes = new ArrayCollection();
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

    public function getOrdering(): ?int
    {
        return $this->ordering;
    }

    public function setOrdering(int $ordering): self
    {
        $this->ordering = $ordering;

        return $this;
    }

    /**
     * @return Collection|ItemPrototype[]
     */
    public function getItemPrototypes(): Collection
    {
        return $this->itemPrototypes;
    }

    public function addItemPrototype(ItemPrototype $itemPrototype): self
    {
        if (!$this->itemPrototypes->contains($itemPrototype)) {
            $this->itemPrototypes[] = $itemPrototype;
            $itemPrototype->setCategory($this);
        }

        return $this;
    }

    public function removeItemPrototype(ItemPrototype $itemPrototype): self
    {
        if ($this->itemPrototypes->contains($itemPrototype)) {
            $this->itemPrototypes->removeElement($itemPrototype);
            // set the owning side to null (unless already changed)
            if ($itemPrototype->getCategory() === $this) {
                $itemPrototype->setCategory(null);
            }
        }

        return $this;
    }
}
