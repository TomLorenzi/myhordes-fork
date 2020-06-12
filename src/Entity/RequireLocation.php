<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RequireLocationRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="require_location_name_unique",columns={"name"})
 * })
 */
class RequireLocation
{
    const LocationInTown = 1;
    const LocationOutside = 2;
    const LocationOutsideFree = 3;
    const LocationOutsideRuin = 4;
    const LocationOutsideBuried = 5;

    const LocationExploring = 6;
    const LocationOutsideOrExploring = 7;

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
    private $location;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minDistance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxDistance;

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

    public function getLocation(): ?int
    {
        return $this->location;
    }

    public function setLocation(int $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getMinDistance(): ?int
    {
        return $this->minDistance;
    }

    public function setMinDistance(?int $minDistance): self
    {
        $this->minDistance = $minDistance;

        return $this;
    }

    public function getMaxDistance(): ?int
    {
        return $this->maxDistance;
    }

    public function setMaxDistance(?int $maxDistance): self
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }
}
