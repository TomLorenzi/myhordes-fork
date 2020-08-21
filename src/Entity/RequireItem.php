<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RequireItemRepository")
 */
class RequireItem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype")
     */
    private $prototype;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemProperty")
     */
    private $property;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $count;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $allowPoison;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProperty(): ?ItemProperty
    {
        return $this->property;
    }

    public function setProperty(?ItemProperty $property): self
    {
        $this->property = $property;

        return $this;
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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getAllowPoison(): ?bool
    {
        return $this->allowPoison;
    }

    public function setAllowPoison(?bool $allowPoison): self
    {
        $this->allowPoison = $allowPoison;

        return $this;
    }
}
