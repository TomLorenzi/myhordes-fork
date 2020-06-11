<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BuildingVoteRepository")
 */
class BuildingVote
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Building::class, inversedBy="buildingVotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $building;

    /**
     * @ORM\OneToOne(targetEntity=Citizen::class, inversedBy="buildingVote", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
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
