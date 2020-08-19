<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CauseOfDeathRepository")
 * @UniqueEntity("ref")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="ref_unique",columns={"ref"})
 * })
 */
class CauseOfDeath
{
    const Unknown = 1;
    const NightlyAttack = 2;
    const Vanished = 3;
    const Dehydration = 4;
    const GhulStarved = 5;
    const Addiction = 6;
    const Infection = 7;
    const Cyanide = 8;
    const Poison = 9;
    const GhulEaten = 10;
    const GhulBeaten = 11;
    const Hanging = 12;
    const FleshCage = 13;
    const Strangulation = 14;
    const Headshot = 15;
    const Radiations = 16;
    const Haunted = 17;
    const ExplosiveDoormat = 18;
    const ChocolateCross = 19;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $label;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $ref;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRef(): ?int
    {
        return $this->ref;
    }

    public function setRef(int $ref): self
    {
        $this->ref = $ref;

        return $this;
    }
}
