<?php

namespace App\Entity;

use App\Interfaces\RandomEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ZonePrototypeRepository")
 */
class ZonePrototype implements RandomEntry
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $campingLevel;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemGroup", cascade={"persist","remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $drops;

    /**
     * @ORM\Column(type="integer")
     */
    private $minDistance;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxDistance;

    /**
     * @ORM\Column(type="integer")
     */
    private $chance;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $icon;

    /**
     * @ORM\Column(type="boolean")
     */
    private $explorable = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCampingLevel(): ?int
    {
        return $this->campingLevel;
    }

    public function setCampingLevel(int $campingLevel): self
    {
        $this->campingLevel = $campingLevel;

        return $this;
    }

    public function getDrops(): ?ItemGroup
    {
        return $this->drops;
    }

    public function setDrops(?ItemGroup $drops): self
    {
        $this->drops = $drops;

        return $this;
    }

    public function getMinDistance(): ?int
    {
        return $this->minDistance;
    }

    public function setMinDistance(int $minDistance): self
    {
        $this->minDistance = $minDistance;

        return $this;
    }

    public function getMaxDistance(): ?int
    {
        return $this->maxDistance;
    }

    public function setMaxDistance(int $maxDistance): self
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }

    public function getChance(): ?int
    {
        return $this->chance;
    }

    public function setChance(int $chance): self
    {
        $this->chance = $chance;

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

    public function getExplorable(): ?bool
    {
        return $this->explorable;
    }

    public function setExplorable(bool $explorable): self
    {
        $this->explorable = $explorable;

        return $this;
    }
}
