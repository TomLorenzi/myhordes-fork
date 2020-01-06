<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ZoneRepository")
 * @UniqueEntity("gps")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="gps_unique",columns={"x","y","town_id"})
 * })
 */
class Zone
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $x;

    /**
     * @ORM\Column(type="integer")
     */
    private $y;

    /**
     * @ORM\Column(type="integer")
     */
    private $zombies;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="zone", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $floor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="zones")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Citizen", mappedBy="zone")
     */
    private $citizens;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getX(): ?int
    {
        return $this->x;
    }

    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): ?int
    {
        return $this->y;
    }

    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function getZombies(): ?int
    {
        return $this->zombies;
    }

    public function setZombies(int $zombies): self
    {
        $this->zombies = $zombies;

        return $this;
    }

    public function getFloor(): ?Inventory
    {
        return $this->floor;
    }

    public function setFloor(Inventory $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    /**
     * @return Collection|Citizen[]
     */
    public function getCitizens(): Collection
    {
        return $this->citizens;
    }

    public function addCitizen(Citizen $citizen): self
    {
        if (!$this->citizens->contains($citizen)) {
            $this->citizens[] = $citizen;
            $citizen->setZone($this);
        }

        return $this;
    }

    public function removeCitizen(Citizen $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
            // set the owning side to null (unless already changed)
            if ($citizen->getZone() === $this) {
                $citizen->setZone(null);
            }
        }

        return $this;
    }
}
