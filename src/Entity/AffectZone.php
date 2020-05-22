<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AffectZoneRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="affect_zone_name_unique",columns={"name"})
 * })
 */
class AffectZone
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
    private $uncoverZones;

    /**
     * @ORM\Column(type="boolean")
     */
    private $uncoverRuin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $escape;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $improveLevel;

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

    public function getUncoverZones(): ?bool
    {
        return $this->uncoverZones;
    }

    public function setUncoverZones(bool $uncoverZones): self
    {
        $this->uncoverZones = $uncoverZones;

        return $this;
    }

    public function getUncoverRuin(): ?bool
    {
        return $this->uncoverRuin;
    }

    public function setUncoverRuin(bool $uncoverRuin): self
    {
        $this->uncoverRuin = $uncoverRuin;

        return $this;
    }

    public function getEscape(): ?int
    {
        return $this->escape;
    }

    public function setEscape(?int $escape): self
    {
        $this->escape = $escape;

        return $this;
    }

    public function getImproveLevel(): ?float
    {
      return $this->improveLevel;
    }

    public function setImproveLevel(?float $improveLevel): self
    {
      $this->improveLevel = $improveLevel;

      return $this;
    }
}
