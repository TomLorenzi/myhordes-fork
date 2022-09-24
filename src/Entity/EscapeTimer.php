<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\EscapeTimerRepository')]
class EscapeTimer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Zone', inversedBy: 'escapeTimers')]
    #[ORM\JoinColumn(nullable: false)]
    private $zone;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen')]
    private $citizen;
    #[ORM\Column(type: 'datetime')]
    private $time;
    #[ORM\Column(type: 'boolean')]
    private $desperate = false;
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
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }
    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }
    public function getDesperate(): ?bool
    {
        return $this->desperate;
    }
    public function setDesperate(bool $desperate): self
    {
        $this->desperate = $desperate;

        return $this;
    }
}
