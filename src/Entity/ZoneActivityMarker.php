<?php

namespace App\Entity;

use App\Enum\ZoneActivityMarkerType;
use App\Repository\ZoneActivityMarkerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZoneActivityMarkerRepository::class)]
class ZoneActivityMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', enumType: ZoneActivityMarkerType::class)]
    private ?ZoneActivityMarkerType $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\ManyToOne(inversedBy: 'activityMarkers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Zone $zone = null;

    #[ORM\ManyToOne(inversedBy: 'zoneActivityMarkers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Citizen $citizen = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?ZoneActivityMarkerType
    {
        return $this->type;
    }

    public function setType(ZoneActivityMarkerType $type): self
    {
        $this->type = $type;

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
