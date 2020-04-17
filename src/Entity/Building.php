<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BuildingRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="town_unique",columns={"prototype_id","town_id"})
 * })
 */
class Building
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BuildingPrototype", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="boolean")
     */
    private $complete = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $ap = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="buildings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DailyUpgradeVote", mappedBy="building", orphanRemoval=true)
     */
    private $dailyUpgradeVotes;

    /**
     * @ORM\Column(type="integer")
     */
    private $level = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $defenseBonus = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $tempDefenseBonus = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $position = 0;

    public function __construct()
    {
        $this->dailyUpgradeVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrototype(): BuildingPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?BuildingPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getComplete(): ?bool
    {
        return $this->complete;
    }

    public function setComplete(bool $complete): self
    {
        $this->complete = $complete;

        return $this;
    }

    public function getAp(): ?int
    {
        return $this->ap;
    }

    public function setAp(int $ap): self
    {
        $this->ap = $ap;

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
     * @return Collection|DailyUpgradeVote[]
     */
    public function getDailyUpgradeVotes(): Collection
    {
        return $this->dailyUpgradeVotes;
    }

    public function addDailyUpgradeVote(DailyUpgradeVote $dailyUpgradeVote): self
    {
        if (!$this->dailyUpgradeVotes->contains($dailyUpgradeVote)) {
            $this->dailyUpgradeVotes[] = $dailyUpgradeVote;
            $dailyUpgradeVote->setBuilding($this);
        }

        return $this;
    }

    public function removeDailyUpgradeVote(DailyUpgradeVote $dailyUpgradeVote): self
    {
        if ($this->dailyUpgradeVotes->contains($dailyUpgradeVote)) {
            $this->dailyUpgradeVotes->removeElement($dailyUpgradeVote);
            // set the owning side to null (unless already changed)
            if ($dailyUpgradeVote->getBuilding() === $this) {
                $dailyUpgradeVote->setBuilding(null);
            }
        }

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getDefenseBonus(): ?int
    {
        return $this->defenseBonus;
    }

    public function setDefenseBonus(int $defenseBonus): self
    {
        $this->defenseBonus = $defenseBonus;

        return $this;
    }

    public function getTempDefenseBonus(): ?int
    {
        return $this->tempDefenseBonus;
    }

    public function setTempDefenseBonus(int $tempDefenseBonus): self
    {
        $this->tempDefenseBonus = $tempDefenseBonus;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
