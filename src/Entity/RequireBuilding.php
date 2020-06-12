<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RequireBuildingRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="require_building_name_unique",columns={"name"})
 * })
 */
class RequireBuilding
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $found;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $complete;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxLevel;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BuildingPrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $building;

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

    public function getFound(): ?bool
    {
        return $this->found;
    }

    public function setFound(?bool $found): self
    {
        $this->found = $found;

        return $this;
    }

    public function getComplete(): ?bool
    {
        return $this->complete;
    }

    public function setComplete(?bool $complete): self
    {
        $this->complete = $complete;

        return $this;
    }

    public function getMinLevel(): ?int
    {
        return $this->minLevel;
    }

    public function setMinLevel(?int $minLevel): self
    {
        $this->minLevel = $minLevel;

        return $this;
    }

    public function getMaxLevel(): ?int
    {
        return $this->maxLevel;
    }

    public function setMaxLevel(?int $maxLevel): self
    {
        $this->maxLevel = $maxLevel;

        return $this;
    }

    public function getBuilding(): ?BuildingPrototype
    {
        return $this->building;
    }

    public function setBuilding(?BuildingPrototype $building): self
    {
        $this->building = $building;

        return $this;
    }
}
