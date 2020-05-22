<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemPropertyRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="item_property_name_unique",columns={"name"})
 * })
 */
class ItemProperty
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
     * @ORM\ManyToMany(targetEntity="App\Entity\ItemPrototype", mappedBy="properties")
     */
    private $itemPrototypes;

    public function __construct()
    {
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
            $itemPrototype->addProperty($this);
        }

        return $this;
    }

    public function removeItemPrototype(ItemPrototype $itemPrototype): self
    {
        if ($this->itemPrototypes->contains($itemPrototype)) {
            $this->itemPrototypes->removeElement($itemPrototype);
            $itemPrototype->removeProperty($this);
        }

        return $this;
    }
}
