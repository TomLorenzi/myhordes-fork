<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: 'App\Repository\SeasonRepository')]
#[Table]
#[UniqueConstraint(name: 'season_unique', columns: ['number', 'sub_number'])]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $number;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Town', mappedBy: 'season')]
    private $towns;
    #[ORM\OneToMany(targetEntity: TownRankingProxy::class, mappedBy: 'season')]
    private $rankedTowns;
    #[ORM\Column(type: 'boolean')]
    private $current = false;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $subNumber;
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: SeasonRankingRange::class, fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $rankingRanges;
    public function __construct()
    {
        $this->towns = new ArrayCollection();
        $this->rankedTowns = new ArrayCollection();
        $this->rankingRanges = new ArrayCollection();
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
    public function getSubNumber(): ?int
    {
        return $this->subNumber;
    }
    public function setSubNumber(?int $subNumber): self
    {
        $this->subNumber = $subNumber;

        return $this;
    }

    /**
     * @return Collection<int, SeasonRankingRange>|PersistentCollection<SeasonRankingRange>
     */
    public function getRankingRanges(): Collection|PersistentCollection
    {
        return $this->rankingRanges;
    }

    /**
     * @param TownClass $class
     * @return SeasonRankingRange|null
     */
    public function getRankingRange(TownClass $class): ?SeasonRankingRange
    {
        return $this->getRankingRanges()->matching( (new Criteria())
            ->where( new Comparison( 'type', Comparison::EQ, $class )  )
        )->first() ?: null;
    }

    public function addRankingRange(SeasonRankingRange $rankingRange): static
    {
        if (!$this->rankingRanges->contains($rankingRange)) {
            $this->rankingRanges->add($rankingRange);
            $rankingRange->setSeason($this);
        }

        return $this;
    }

    public function removeRankingRange(SeasonRankingRange $rankingRange): static
    {
        if ($this->rankingRanges->removeElement($rankingRange)) {
            // set the owning side to null (unless already changed)
            if ($rankingRange->getSeason() === $this) {
                $rankingRange->setSeason(null);
            }
        }

        return $this;
    }
}
