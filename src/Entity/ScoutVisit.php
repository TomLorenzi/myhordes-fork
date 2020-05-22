<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScoutVisitRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="scout_visit_assoc_unique",columns={"scout_id","zone_id"})
 * })
 */
class ScoutVisit
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Citizen")
     * @ORM\JoinColumn(nullable=false)
     */
    private $scout;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Zone", inversedBy="scoutVisits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $zone;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScout(): ?Citizen
    {
        return $this->scout;
    }

    public function setScout(?Citizen $scout): self
    {
        $this->scout = $scout;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }
}
