<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\DigRuinMarkerRepository')]
class DigRuinMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Zone', inversedBy: 'digRuinMarkers')]
    #[ORM\JoinColumn(nullable: false)]
    private $zone;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen')]
    #[ORM\JoinColumn(nullable: false)]
    private $citizen;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
}
