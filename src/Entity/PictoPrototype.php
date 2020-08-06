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
 * @ORM\Entity(repositoryClass="App\Repository\PictoPrototypeRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="picto_prototype_name_unique",columns={"name"})
 * })
 */
class PictoPrototype implements NamedEntity
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
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $icon;

    /**
     * @ORM\Column(type="boolean")
     */
    private $rare;

    /**
     * @ORM\OneToMany(targetEntity=AwardPrototype::class, mappedBy="associatedPicto", orphanRemoval=true)
     */
    private $awards;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getRare(): ?bool
    {
        return $this->rare;
    }

    public function setRare(bool $rare): self
    {
        $this->rare = $rare;

        return $this;
    }

    /**
     * @return Collection|AwardPrototype[]
     */
    public function getAwards(): Collection
    {
        return $this->awards;
    }

    public function addAward(AwardPrototype $award): self
    {
        if (!$this->awards->contains($award)) {
            $this->awards[] = $award;
            $award->setAssociatedPicto($this);
        }

        return $this;
    }

    public function removeAward(AwardPrototype $award): self
    {
        if ($this->awards->contains($award)) {
            $this->awards->removeElement($award);
            // set the owning side to null (unless already changed)
            if ($award->getAssociatedPicto() === $this) {
                $award->setAssociatedPicto(null);
            }
        }

        return $this;
    }
}
