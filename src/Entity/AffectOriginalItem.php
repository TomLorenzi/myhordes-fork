<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectOriginalItemRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_original_item_name_unique",columns={"name"})
 * })
 */
class AffectOriginalItem
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
     * @ORM\Column(type="boolean")
     */
    private $consume;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemPrototype")
     */
    private $morph;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $break;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $poison;

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

    public function getConsume(): ?bool
    {
        return $this->consume;
    }

    public function setConsume(bool $consume): self
    {
        $this->consume = $consume;

        return $this;
    }

    public function getMorph(): ?ItemPrototype
    {
        return $this->morph;
    }

    public function setMorph(?ItemPrototype $morph): self
    {
        $this->morph = $morph;

        return $this;
    }

    public function getBreak(): ?bool
    {
        return $this->break;
    }

    public function setBreak(?bool $break): self
    {
        $this->break = $break;

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
}
