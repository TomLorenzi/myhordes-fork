<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectBlueprintRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_blueprint_name_unique",columns={"name"})
 * })
 */
class AffectBlueprint
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
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\BuildingPrototype")
     */
    private $list;

    public function __construct()
    {
        $this->list = new ArrayCollection();
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

    /**
     * @return Collection|BuildingPrototype[]
     */
    public function getList(): Collection
    {
        return $this->list;
    }

    public function addList(BuildingPrototype $list): self
    {
        if (!$this->list->contains($list)) {
            $this->list[] = $list;
        }

        return $this;
    }

    public function removeList(BuildingPrototype $list): self
    {
        if ($this->list->contains($list)) {
            $this->list->removeElement($list);
        }

        return $this;
    }
}
