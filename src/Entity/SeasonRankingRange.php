<?php

namespace App\Entity;

use App\Repository\SeasonRankingRangeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: SeasonRankingRangeRepository::class)]
#[Table]
#[UniqueConstraint(name: 'season_ranking_range_unique', columns: ['season_id', 'type_id'])]
class SeasonRankingRange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rankingRanges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TownClass $type = null;

    #[ORM\Column]
    private ?int $top = null;

    #[ORM\Column]
    private ?int $mid = null;

    #[ORM\Column]
    private ?int $low = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getType(): ?TownClass
    {
        return $this->type;
    }

    public function setType(?TownClass $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTop(): ?int
    {
        return $this->top;
    }

    public function setTop(int $top): static
    {
        $this->top = $top;

        return $this;
    }

    public function getMid(): ?int
    {
        return $this->mid;
    }

    public function setMid(int $mid): static
    {
        $this->mid = $mid;

        return $this;
    }

    public function getLow(): ?int
    {
        return $this->low;
    }

    public function setLow(int $low): static
    {
        $this->low = $low;

        return $this;
    }
}
