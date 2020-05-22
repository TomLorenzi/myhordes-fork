<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ZombieEstimationRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="zombie_estimation_town_day_unique",columns={"town_id","day"})
 * })
 */
class ZombieEstimation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="zombieEstimations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\Column(type="integer")
     */
    private $day = 1;

    /**
     * @ORM\Column(type="integer")
     */
    private $zombies;

    /**
     * @ORM\Column(type="integer")
     */
    private $offsetMin;

    /**
     * @ORM\Column(type="integer")
     */
    private $offsetMax;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Citizen")
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

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

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

    public function getOffsetMin(): ?int
    {
        return $this->offsetMin;
    }

    public function setOffsetMin(int $offsetMin): self
    {
        $this->offsetMin = $offsetMin;

        return $this;
    }

    public function getOffsetMax(): ?int
    {
        return $this->offsetMax;
    }

    public function setOffsetMax(int $offsetMax): self
    {
        $this->offsetMax = $offsetMax;

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
        }

        return $this;
    }

    public function removeCitizen(Citizen $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
        }

        return $this;
    }
}
