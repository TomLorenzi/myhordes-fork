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
 *     @UniqueConstraint(name="town_day_unique",columns={"town_id","day"})
 * })
 */
class Gazette
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="gazettes")
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
    private $attack = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $defense = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $deaths = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $terror = 0;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Citizen")
     */
    private $victims;

    public function __construct()
    {
        $this->victims = new ArrayCollection();
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

    public function getAttack(): ?int
    {
        return $this->attack;
    }

    public function setAttack(int $attack): self
    {
        $this->attack = $attack;

        return $this;
    }

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): self
    {
        $this->defense = $defense;

        return $this;
    }

    public function getDeaths(): ?int
    {
        return $this->deaths;
    }

    public function setDeaths(int $deaths): self
    {
        $this->deaths = $deaths;

        return $this;
    }

    public function getTerror(): ?int
    {
        return $this->terror;
    }

    public function setTerror(int $terror): self
    {
        $this->terror = $terror;

        return $this;
    }

    /**
     * @return Collection|Citizen[]
     */
    public function getVictims(): Collection
    {
        return $this->victims;
    }

    public function addVictim(Citizen $victim): self
    {
        if (!$this->victims->contains($victim)) {
            $this->victims[] = $victim;
        }

        return $this;
    }

    public function removeVictim(Citizen $victim): self
    {
        if ($this->victims->contains($victim)) {
            $this->victims->removeElement($victim);
        }

        return $this;
    }
}
