<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\DailyUpgradeVoteRepository')]
class DailyUpgradeVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Building', inversedBy: 'dailyUpgradeVotes')]
    #[ORM\JoinColumn(nullable: false)]
    private $building;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Citizen', inversedBy: 'dailyUpgradeVote', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $citizen;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getBuilding(): ?Building
    {
        return $this->building;
    }
    public function setBuilding(?Building $building): self
    {
        $this->building = $building;

        return $this;
    }
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
}
