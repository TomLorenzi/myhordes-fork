<?php

namespace App\Entity;

use App\Repository\RuinExplorerStatsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: RuinExplorerStatsRepository::class)]
#[Table]
#[UniqueConstraint(name: 'explorer_unique', columns: ['citizen_id', 'zone_id'])]
class RuinExplorerStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Citizen::class, inversedBy: 'explorerStats', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $citizen;

    #[ORM\ManyToOne(targetEntity: Zone::class, inversedBy: 'explorerStats', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $zone;

    #[ORM\Column(type: 'integer')]
    private $x = 0;

    #[ORM\Column(type: 'integer')]
    private $y = 0;

    #[ORM\Column(type: 'integer')]
    private $z = 0;

    #[ORM\Column(type: 'boolean')]
    private $active;

    #[ORM\Column(type: 'boolean')]
    private $inRoom = false;

    #[ORM\ManyToMany(targetEntity: RuinZone::class)]
    private $scavengedRooms;

    #[ORM\Column(type: 'boolean')]
    private $escaping = false;

    #[ORM\Column(type: 'datetime')]
    private $timeout;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $started = null;

    #[ORM\Column]
    private bool $grace = false;

    public function __construct()
    {
        $this->scavengedRooms = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;
        $this->setZone( $citizen !== null ? $citizen->getZone() : null );

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
    public function getX(): ?int
    {
        return $this->x;
    }
    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }
    public function getY(): ?int
    {
        return $this->y;
    }
    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }
    public function getZ(): ?int
    {
        return $this->z;
    }
    public function setZ(int $z): self
    {
        $this->z = $z;

        return $this;
    }
    public function getActive(): ?bool
    {
        return $this->active;
    }
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
    public function getInRoom(): ?bool
    {
        return $this->inRoom;
    }
    public function setInRoom(bool $inRoom): self
    {
        $this->inRoom = $inRoom;

        return $this;
    }
    /**
     * @return Collection|RuinZone[]
     */
    public function getScavengedRooms(): Collection
    {
        return $this->scavengedRooms;
    }
    public function addScavengedRoom(RuinZone $scavengedRoom): self
    {
        if (!$this->scavengedRooms->contains($scavengedRoom)) {
            $this->scavengedRooms[] = $scavengedRoom;
        }

        return $this;
    }
    public function removeScavengedRoom(RuinZone $scavengedRoom): self
    {
        if ($this->scavengedRooms->contains($scavengedRoom)) {
            $this->scavengedRooms->removeElement($scavengedRoom);
        }

        return $this;
    }
    public function getEscaping(): ?bool
    {
        return $this->escaping;
    }
    public function setEscaping(bool $escaping): self
    {
        $this->escaping = $escaping;

        return $this;
    }
    public function getTimeout(): ?\DateTimeInterface
    {
        return $this->timeout;
    }
    public function setTimeout(\DateTimeInterface $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getStarted(): ?\DateTimeInterface
    {
        return $this->started;
    }

    public function setStarted(?\DateTimeInterface $started): static
    {
        $this->started = $started;

        return $this;
    }

    public function isGrace(): ?bool
    {
        return $this->grace;
    }

    public function setGrace(bool $grace): static
    {
        $this->grace = $grace;

        return $this;
    }
}
