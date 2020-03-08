<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ItemTargetDefinitionRepository")
 */
class ItemTargetDefinition
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $broken;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $poison;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $heavy;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemProperty")
     */
    private $tag;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype")
     */
    private $prototype;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBroken(): ?bool
    {
        return $this->broken;
    }

    public function setBroken(?bool $broken): self
    {
        $this->broken = $broken;

        return $this;
    }

    public function getPoison(): ?bool
    {
        return $this->poison;
    }

    public function setPoison(?bool $poison): self
    {
        $this->poison = $poison;

        return $this;
    }

    public function getHeavy(): ?bool
    {
        return $this->heavy;
    }

    public function setHeavy(?bool $heavy): self
    {
        $this->heavy = $heavy;

        return $this;
    }

    public function getTag(): ?ItemProperty
    {
        return $this->tag;
    }

    public function setTag(?ItemProperty $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getPrototype(): ?ItemPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?ItemPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }
}
