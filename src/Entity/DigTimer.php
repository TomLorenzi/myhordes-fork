<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\DigTimerRepository')]
#[Table]
#[UniqueConstraint(name: 'dig_timer_assoc_unique', columns: ['citizen_id', 'zone_id'])]
class DigTimer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Zone', inversedBy: 'digTimers')]
    #[ORM\JoinColumn(nullable: false)]
    private $zone;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Citizen', inversedBy: 'digTimers')]
    #[ORM\JoinColumn(nullable: false)]
    private $citizen;
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    #[ORM\Column(type: 'boolean')]
    private $passive = false;
    #[ORM\Column(type: 'array', nullable: true)]
    private $digCache = [];

    #[ORM\Column]
    private bool $nonAutomatic = false;

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
    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }
    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
    public function getPassive(): ?bool
    {
        return $this->passive;
    }
    public function setPassive(bool $passive): self
    {
        $this->passive = $passive;

        return $this;
    }
    public function getDigCache(): ?array
    {
        return $this->digCache;
    }
    public function setDigCache(?array $digCache): self
    {
        $this->digCache = $digCache;

        return $this;
    }

    public function isNonAutomatic(): bool
    {
        return $this->nonAutomatic;
    }

    public function setNonAutomatic(bool $nonAutomatic): self
    {
        $this->nonAutomatic = $nonAutomatic;

        return $this;
    }
}
