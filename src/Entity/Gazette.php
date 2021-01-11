<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GazetteRepository")
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
     * @ORM\OneToMany(targetEntity="App\Entity\GazetteLogEntry", mappedBy="gazette", cascade={"persist","remove"})
     */
    private $_log_entries;

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
    private $invasion = 0;

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

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $door;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $windDirection = 0;

    /**
     * @ORM\Column(type="integer",  nullable=true)
     */
    private $waterlost = 0;

    /**
     * @ORM\ManyToMany(targetEntity=CitizenRole::class)
     */
    private $votes_needed;

    public function __construct()
    {
        $this->victims = new ArrayCollection();
        $this->votes_needed = new ArrayCollection();
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

    public function getInvasion(): ?int
    {
        return $this->invasion;
    }

    public function setInvasion(int $invasion): self
    {
        $this->invasion = $invasion;

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

    public function getDoor(): ?bool
    {
        return $this->door;
    }

    public function setDoor(?bool $door): self
    {
        $this->door = $door;

        return $this;
    }

    public function getWindDirection(): ?int
    {
        return $this->windDirection;
    }

    public function setWindDirection(?int $windDirection): self
    {
        $this->windDirection = $windDirection;

        return $this;
    }

    public function getWaterlost(): ?int
    {
        return $this->waterlost;
    }

    public function setWaterlost(int $waterlost): self
    {
        $this->waterlost = $waterlost;

        return $this;
    }

    /**
     * @return Collection|CitizenRole[]
     */
    public function getVotesNeeded(): Collection
    {
        return $this->votes_needed;
    }

    public function addVotesNeeded(CitizenRole $votesNeeded): self
    {
        if (!$this->votes_needed->contains($votesNeeded)) {
            $this->votes_needed[] = $votesNeeded;
        }

        return $this;
    }

    public function removeVotesNeeded(CitizenRole $votesNeeded): self
    {
        $this->votes_needed->removeElement($votesNeeded);

        return $this;
    }
}
