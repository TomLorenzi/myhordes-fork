<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeasonRepository")
 */
class Season
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
    private $number;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Town", mappedBy="season")
     */
    private $towns;

    /**
     * @ORM\OneToMany(targetEntity=TownRankingProxy::class, mappedBy="season")
     */
    private $rankedTowns;

    /**
     * @ORM\Column(type="boolean")
     */
    private $current = false;

    public function __construct()
    {
        $this->towns = new ArrayCollection();
        $this->rankedTowns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
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

    /**
     * @return Collection|Town[]
     */
    public function getTowns(): Collection
    {
        return $this->towns;
    }

    public function addTown(Town $town): self
    {
        if (!$this->towns->contains($town)) {
            $this->towns[] = $town;
            $town->setSeason($this);
        }

        return $this;
    }

    public function removeTown(Town $town): self
    {
        if ($this->towns->contains($town)) {
            $this->towns->removeElement($town);
            // set the owning side to null (unless already changed)
            if ($town->getSeason() === $this) {
                $town->setSeason(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TownRankingProxy[]
     */
    public function getRankedTowns(): Collection
    {
        return $this->rankedTowns;
    }

    public function addRankedTown(TownRankingProxy $rankedTown): self
    {
        if (!$this->rankedTowns->contains($rankedTown)) {
            $this->rankedTowns[] = $rankedTown;
            $rankedTown->setSeason($this);
        }

        return $this;
    }

    public function removeRankedTown(TownRankingProxy $rankedTown): self
    {
        if ($this->rankedTowns->contains($rankedTown)) {
            $this->rankedTowns->removeElement($rankedTown);
            // set the owning side to null (unless already changed)
            if ($rankedTown->getSeason() === $this) {
                $rankedTown->setSeason(null);
            }
        }

        return $this;
    }

    public function getCurrent(): ?bool
    {
        return $this->current;
    }

    public function setCurrent(bool $current): self
    {
        $this->current = $current;

        return $this;
    }
}
