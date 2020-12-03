<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenProfessionRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="citizen_profession_name_unique",columns={"name"})
 * })
 */
class CitizenProfession implements NamedEntity
{
    const DEFAULT = 'none';

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
     * @ORM\Column(type="string", length=32)
     */
    private $icon;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\ItemPrototype")
     */
    private $professionItems;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\ItemPrototype")
     * @JoinTable(name="citizen_profession_item_prototype_alt")
     */
    private $altProfessionItems;

    /**
     * @ORM\Column(type="boolean")
     */
    private $heroic;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    public function __construct()
    {
        $this->professionItems = new ArrayCollection();
        $this->altProfessionItems = new ArrayCollection();
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return Collection|ItemPrototype[]
     */
    public function getProfessionItems(): Collection
    {
        return $this->professionItems;
    }

    public function addProfessionItem(ItemPrototype $professionItem): self
    {
        if (!$this->professionItems->contains($professionItem)) {
            $this->professionItems[] = $professionItem;
        }

        return $this;
    }

    public function removeProfessionItem(ItemPrototype $professionItem): self
    {
        if ($this->professionItems->contains($professionItem)) {
            $this->professionItems->removeElement($professionItem);
        }

        return $this;
    }

    /**
     * @return Collection|ItemPrototype[]
     */
    public function getAltProfessionItems(): Collection
    {
        return $this->altProfessionItems;
    }

    public function addAltProfessionItem(ItemPrototype $altProfessionItem): self
    {
        if (!$this->altProfessionItems->contains($altProfessionItem)) {
            $this->altProfessionItems[] = $altProfessionItem;
        }

        return $this;
    }

    public function removeAltProfessionItem(ItemPrototype $altProfessionItem): self
    {
        if ($this->altProfessionItems->contains($altProfessionItem)) {
            $this->altProfessionItems->removeElement($altProfessionItem);
        }

        return $this;
    }

    public function getHeroic(): ?bool
    {
        return $this->heroic;
    }

    public function setHeroic(bool $heroic): self
    {
        $this->heroic = $heroic;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
