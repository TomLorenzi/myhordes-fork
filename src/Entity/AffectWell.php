<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectWellRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_well_name_unique",columns={"name"})
 * })
 */
class AffectWell
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
    private $fillMin;

    /**
     * @ORM\Column(type="integer")
     */
    private $fillMax;

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

    public function getFillMin(): ?int
    {
        return $this->fillMin;
    }

    public function setFillMin(int $fillMin): self
    {
        $this->fillMin = $fillMin;

        return $this;
    }

    public function getFillMax(): ?int
    {
        return $this->fillMax;
    }

    public function setFillMax(int $fillMax): self
    {
        $this->fillMax = $fillMax;

        return $this;
    }
}
